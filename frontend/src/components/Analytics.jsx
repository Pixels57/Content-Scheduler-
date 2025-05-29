import React, { useState, useEffect } from 'react';
import axios from '../api/axios';
import '../styles/Analytics.css';

function Analytics() {
  const [analytics, setAnalytics] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const fetchAnalytics = async () => {
      setIsLoading(true);
      try {
        const response = await axios.get('/post-analytics');
        setAnalytics(response.data.data);
      } catch (err) {
        setError('Failed to load analytics data. Please try again.');
        console.error('Error loading analytics:', err);
      } finally {
        setIsLoading(false);
      }
    };

    fetchAnalytics();
  }, []);

  // Function to get status color class
  const getStatusClass = (status) => {
    switch (status) {
      case 'draft': return 'status-draft';
      case 'scheduled': return 'status-scheduled';
      case 'published': return 'status-published';
      default: return '';
    }
  };

  // Function to get platform icon/color
  const getPlatformClass = (type) => {
    switch (type) {
      case 'twitter': return 'platform-twitter';
      case 'instagram': return 'platform-instagram';
      case 'facebook': return 'platform-facebook';
      case 'linkedin': return 'platform-linkedin';
      default: return 'platform-other';
    }
  };

  return (
    <div className="analytics-container">
      <div className="analytics-header">
        <h1>Post Analytics</h1>
      </div>

      {error && <div className="error-message">{error}</div>}

      {isLoading ? (
        <div className="loading">Loading analytics data...</div>
      ) : analytics ? (
        <div className="analytics-content">
          <div className="analytics-summary">
            <div className="analytics-card total-posts">
              <h3>Total Posts</h3>
              <div className="analytics-value">{analytics.total_posts}</div>
            </div>
            
            <div className="analytics-card success-rate">
              <h3>Publishing Success Rate</h3>
              <div className="analytics-value">{analytics.success_rate}%</div>
              <div className="analytics-description">
                Posts published successfully vs. total scheduled
              </div>
            </div>
          </div>
          
          <div className="analytics-section">
            <h2>Posts by Status</h2>
            <div className="status-distribution">
              {Object.keys(analytics.posts_by_status).length > 0 ? (
                <div className="status-chart">
                  {Object.entries(analytics.posts_by_status).map(([status, count]) => (
                    <div key={status} className="status-bar-container">
                      <div className="status-label">{status}</div>
                      <div className="status-bar-wrapper">
                        <div 
                          className={`status-bar ${getStatusClass(status)}`}
                          style={{ width: `${analytics.status_percentages[status]}%` }}
                        ></div>
                        <div className="status-count">{count} ({analytics.status_percentages[status]}%)</div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="no-data">No posts available</div>
              )}
            </div>
          </div>
          
          <div className="analytics-section">
            <h2>Posts by Platform</h2>
            <div className="platform-distribution">
              {analytics.platforms.length > 0 ? (
                <div className="platform-grid">
                  {analytics.platforms.map(platform => (
                    <div key={platform.id} className={`platform-card ${getPlatformClass(platform.type)}`}>
                      <h3>{platform.name}</h3>
                      <div className="platform-count">{platform.post_count}</div>
                      <div className="platform-percentage">
                        {analytics.total_posts > 0 
                          ? Math.round((platform.post_count / analytics.total_posts) * 100)
                          : 0}% of total posts
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="no-data">No platform data available</div>
              )}
            </div>
          </div>
          
          <div className="analytics-tips">
            <h3>Tips Based on Your Data</h3>
            <ul>
              {analytics.posts_by_status.draft > analytics.posts_by_status.published && (
                <li>You have more drafts than published posts. Consider scheduling your drafts for publication.</li>
              )}
              {analytics.success_rate < 70 && analytics.total_posts > 0 && (
                <li>Your publishing success rate is below 70%. Check if your scheduled posts are being published correctly.</li>
              )}
              {analytics.platforms.some(p => p.post_count === 0) && (
                <li>You're not using some platforms. Consider diversifying your content across all available platforms.</li>
              )}
            </ul>
          </div>
        </div>
      ) : (
        <div className="no-data">No analytics data available</div>
      )}
    </div>
  );
}

export default Analytics; 