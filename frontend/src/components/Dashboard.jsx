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
  
  // State for delete confirmation
  const [deleteModal, setDeleteModal] = useState({
    isOpen: false,
    postId: null,
    postTitle: ''
  });
  
  // Function to handle post deletion
  const handleDelete = async (postId) => {
    try {
      setIsLoading(true);
      const response = await axios.delete(`/posts/${postId}`);
      
      // Check for success status in response
      if (response.data && response.data.status === 'success') {
        // Remove the deleted post from state
        setPosts(prevPosts => prevPosts.filter(post => post.id !== postId));
        
        // Also update selectedDayPosts if applicable
        if (selectedDate) {
          setSelectedDayPosts(prevPosts => prevPosts.filter(post => post.id !== postId));
        }
        
        // Close the modal
        setDeleteModal({
          isOpen: false,
          postId: null,
          postTitle: ''
        });
      } else {
        throw new Error('Failed to delete post');
      }
    } catch (err) {
      setError(
        err.response?.data?.message || 
        'Failed to delete post. Please try again.'
      );
      console.error('Error deleting post:', err);
    } finally {
      setIsLoading(false);
    }
  };

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
        
        // For scheduled posts, if no from_date is set, use today's date
        // This ensures we only see future scheduled posts by default
        if (filters.status === 'scheduled' && !filters.from_date) {
          const today = new Date();
          today.setHours(0, 0, 0, 0); // Start of today
          params.from_date = today.toISOString().split('T')[0]; // Format as YYYY-MM-DD
        }

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

  // Get status label with proper capitalization
  const getStatusLabel = (status) => {
    switch (status) {
      case 'draft': return 'Draft';
      case 'scheduled': return 'Scheduled';
      case 'published': return 'Published';
      default: return status;
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
  
  // State for selected day posts
  const [selectedDate, setSelectedDate] = useState(null);
  const [selectedDayPosts, setSelectedDayPosts] = useState([]);
  
  // Handle day click in calendar
  const handleDayClick = (date) => {
    const postsOnDay = getPostsForDay(date);
    setSelectedDate(date);
    setSelectedDayPosts(postsOnDay);
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
          
          <div className="filter-item filter-actions">
            <button 
              className="clear-filters"
              onClick={() => setFilters({
                status: '',
                from_date: '',
                to_date: ''
              })}
            >
              Clear Filters
            </button>
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
              <div className="list-legend">
                <h4>Status Legend:</h4>
                <div className="status-legend-items">
                  <div className="status-legend-item">
                    <div className={`status-indicator ${getStatusClass('draft')}`}></div>
                    <span>Draft</span>
                  </div>
                  <div className="status-legend-item">
                    <div className={`status-indicator ${getStatusClass('scheduled')}`}></div>
                    <span>Scheduled</span>
                  </div>
                  <div className="status-legend-item">
                    <div className={`status-indicator ${getStatusClass('published')}`}></div>
                    <span>Published</span>
                  </div>
                </div>
              </div>
              
              {posts.length === 0 ? (
                <div className="no-posts">No posts found. Create a new post to get started.</div>
              ) : (
                <>
                  {filters.status === 'scheduled' && !filters.from_date && (
                    <div className="filter-info">
                      Showing scheduled posts from today onwards. Use date filters to see past scheduled posts.
                    </div>
                  )}
                  {posts.map(post => (
                    <div key={post.id} className="post-item">
                      <div className={`post-status ${getStatusClass(post.status)}`}>
                        {getStatusLabel(post.status)}
                      </div>
                      {post.image_url && (
                        <div className="post-thumbnail">
                          <img 
                            src={post.image_url} 
                            alt={post.title} 
                            onError={(e) => {
                              e.target.src = "https://via.placeholder.com/60x60?text=Error";
                              e.target.style.opacity = "0.5";
                            }}
                          />
                        </div>
                      )}
                      <div className="post-info">
                        <h3 className="post-title">{post.title}</h3>
                        <div className="post-date">
                          Scheduled for: {formatDate(post.scheduled_time)}
                        </div>
                        <div className="post-platforms">
                          Platforms: {post.platforms && post.platforms.map(p => p.name).join(', ') || 'None'}
                        </div>
                      </div>
                      <div className="post-actions">
                        <Link to={`/posts/${post.id}/edit`} className="btn-edit">
                          Edit
                        </Link>
                        <button 
                          className="btn-delete"
                          onClick={() => setDeleteModal({
                            isOpen: true,
                            postId: post.id,
                            postTitle: post.title
                          })}
                        >
                          Delete
                        </button>
                      </div>
                    </div>
                  ))}
                </>
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
                onClickDay={handleDayClick}
                tileClassName={({ date }) => {
                  // Add a class if this day is selected
                  if (selectedDate && 
                      date.getDate() === selectedDate.getDate() &&
                      date.getMonth() === selectedDate.getMonth() &&
                      date.getFullYear() === selectedDate.getFullYear()) {
                    return 'selected-day';
                  }
                  return null;
                }}
              />
              
              <div className="calendar-legend">
                <div className="legend-item">
                  <div className="legend-indicator"></div>
                  <span>Posts on this day</span>
                </div>
                
                <div className="status-legend">
                  <h4>Post Status Legend:</h4>
                  <div className="status-legend-items">
                    <div className="status-legend-item">
                      <div className={`status-indicator ${getStatusClass('draft')}`}></div>
                      <span>Draft</span>
                    </div>
                    <div className="status-legend-item">
                      <div className={`status-indicator ${getStatusClass('scheduled')}`}></div>
                      <span>Scheduled</span>
                    </div>
                    <div className="status-legend-item">
                      <div className={`status-indicator ${getStatusClass('published')}`}></div>
                      <span>Published</span>
                    </div>
                  </div>
                </div>
              </div>
              
              {/* Display selected day posts */}
              {selectedDate && (
                <div className="calendar-day-posts">
                  <h3 className="day-posts-header">
                    Posts for {selectedDate.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                    <button 
                      className="close-day-posts"
                      onClick={() => setSelectedDate(null)}
                    >
                      &times;
                    </button>
                  </h3>
                  
                  {selectedDayPosts.length > 0 ? (
                    <div className="day-posts-list">
                      {selectedDayPosts.map(post => (
                        <div key={post.id} className="day-post-item">
                          <div className={`post-status ${getStatusClass(post.status)}`}>
                            {getStatusLabel(post.status)}
                          </div>
                          {post.image_url && (
                            <div className="post-thumbnail">
                              <img 
                                src={post.image_url} 
                                alt={post.title}
                                onError={(e) => {
                                  e.target.src = "https://via.placeholder.com/60x60?text=Error";
                                  e.target.style.opacity = "0.5";
                                }}
                              />
                            </div>
                          )}
                          <div className="post-info">
                            <h4 className="post-title">{post.title}</h4>
                            <div className="post-time">
                              Time: {new Date(post.scheduled_time).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })}
                            </div>
                            <div className="post-platforms">
                              Platforms: {post.platforms && post.platforms.map(p => p.name).join(', ') || 'None'}
                            </div>
                          </div>
                          <div className="post-actions">
                            <Link to={`/posts/${post.id}/edit`} className="btn-edit">
                              Edit
                            </Link>
                            <button 
                              className="btn-delete"
                              onClick={() => setDeleteModal({
                                isOpen: true,
                                postId: post.id,
                                postTitle: post.title
                              })}
                            >
                              Delete
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="no-day-posts">No posts scheduled for this day.</p>
                  )}
                </div>
              )}

              {filters.status === 'scheduled' && !filters.from_date && (
                <div className="filter-info calendar-filter-info">
                  Showing scheduled posts from today onwards. Use date filters to see past scheduled posts.
                </div>
              )}
            </div>
          )}
        </>
      )}
      
      {/* Delete Confirmation Modal */}
      {deleteModal.isOpen && (
        <div className="delete-modal-overlay">
          <div className="delete-modal">
            <h3>Delete Post</h3>
            <p>
              Are you sure you want to delete <strong>"{deleteModal.postTitle}"</strong>?
              This action cannot be undone.
            </p>
            <div className="delete-modal-actions">
              <button 
                className="btn-cancel"
                onClick={() => setDeleteModal({
                  isOpen: false,
                  postId: null,
                  postTitle: ''
                })}
              >
                Cancel
              </button>
              <button 
                className="btn-confirm-delete"
                onClick={() => handleDelete(deleteModal.postId)}
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default Dashboard; 