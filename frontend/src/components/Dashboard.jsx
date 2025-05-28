import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import Calendar from 'react-calendar';
import axios from '../api/axios';
import '../styles/Dashboard.css';
import 'react-calendar/dist/Calendar.css';

function Dashboard() {
  const [posts, setPosts] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');
  const [view, setView] = useState('list'); // 'list' or 'calendar'
  const [filters, setFilters] = useState({
    status: '',
    from_date: '',
    to_date: ''
  });

  // Fetch posts with filters
  useEffect(() => {
    const fetchPosts = async () => {
      setIsLoading(true);
      try {
        // Create params object with only non-empty values
        const params = {};
        Object.entries(filters).forEach(([key, value]) => {
          if (value) params[key] = value;
        });

        const response = await axios.get('/posts', { params });
        setPosts(response.data.data);
      } catch (err) {
        setError('Failed to load posts. Please try again.');
        console.error('Error loading posts:', err);
      } finally {
        setIsLoading(false);
      }
    };

    fetchPosts();
  }, [filters]);

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const getStatusClass = (status) => {
    switch (status) {
      case 'draft': return 'status-draft';
      case 'scheduled': return 'status-scheduled';
      case 'published': return 'status-published';
      default: return '';
    }
  };

  // Format date for display
  const formatDate = (dateString) => {
    const options = { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    };
    return new Date(dateString).toLocaleString(undefined, options);
  };

  // Get posts for a specific day (for calendar view)
  const getPostsForDay = (date) => {
    return posts.filter(post => {
      const postDate = new Date(post.scheduled_time);
      return (
        postDate.getDate() === date.getDate() &&
        postDate.getMonth() === date.getMonth() &&
        postDate.getFullYear() === date.getFullYear()
      );
    });
  };

  return (
    <div className="dashboard">
      <div className="dashboard-header">
        <h1>Content Dashboard</h1>
        <Link to="/posts/new" className="btn-new-post">
          Create New Post
        </Link>
      </div>

      <div className="dashboard-controls">
        <div className="view-toggle">
          <button
            className={view === 'list' ? 'active' : ''}
            onClick={() => setView('list')}
          >
            List View
          </button>
          <button
            className={view === 'calendar' ? 'active' : ''}
            onClick={() => setView('calendar')}
          >
            Calendar View
          </button>
        </div>

        <div className="filters">
          <div className="filter-item">
            <label htmlFor="status">Status:</label>
            <select
              id="status"
              name="status"
              value={filters.status}
              onChange={handleFilterChange}
            >
              <option value="">All Statuses</option>
              <option value="draft">Draft</option>
              <option value="scheduled">Scheduled</option>
              <option value="published">Published</option>
            </select>
          </div>

          <div className="filter-item">
            <label htmlFor="from_date">From:</label>
            <input
              type="date"
              id="from_date"
              name="from_date"
              value={filters.from_date}
              onChange={handleFilterChange}
            />
          </div>

          <div className="filter-item">
            <label htmlFor="to_date">To:</label>
            <input
              type="date"
              id="to_date"
              name="to_date"
              value={filters.to_date}
              onChange={handleFilterChange}
            />
          </div>
        </div>
      </div>

      {error && <div className="error-message">{error}</div>}

      {isLoading ? (
        <div className="loading">Loading posts...</div>
      ) : (
        <>
          {view === 'list' ? (
            <div className="post-list">
              {posts.length === 0 ? (
                <div className="no-posts">No posts found. Create a new post to get started.</div>
              ) : (
                posts.map(post => (
                  <div key={post.id} className="post-item">
                    <div className={`post-status ${getStatusClass(post.status)}`}>
                      {post.status}
                    </div>
                    <div className="post-info">
                      <h3 className="post-title">{post.title}</h3>
                      <div className="post-date">
                        Scheduled for: {formatDate(post.scheduled_time)}
                      </div>
                      <div className="post-platforms">
                        Platforms: {post.platforms.map(p => p.name).join(', ') || 'None'}
                      </div>
                    </div>
                    <div className="post-actions">
                      <Link to={`/posts/${post.id}/edit`} className="btn-edit">
                        Edit
                      </Link>
                    </div>
                  </div>
                ))
              )}
            </div>
          ) : (
            <div className="calendar-view">
              <Calendar
                tileContent={({ date }) => {
                  const postsOnDay = getPostsForDay(date);
                  return postsOnDay.length > 0 ? (
                    <div className="calendar-post-indicator">
                      <span>{postsOnDay.length}</span>
                    </div>
                  ) : null;
                }}
                onClickDay={(date) => {
                  const postsOnDay = getPostsForDay(date);
                  if (postsOnDay.length > 0) {
                    // Could show a modal or highlight these posts in the list
                    console.log('Posts on', date, postsOnDay);
                  }
                }}
              />
              
              <div className="calendar-legend">
                <div className="legend-item">
                  <div className="legend-indicator"></div>
                  <span>Posts scheduled on this day</span>
                </div>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
}

export default Dashboard; 