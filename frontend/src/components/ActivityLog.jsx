import React, { useState, useEffect } from 'react';
import axios from '../api/axios';
import '../styles/ActivityLog.css';

function ActivityLog() {
  const [logs, setLogs] = useState([]);
  const [summary, setSummary] = useState(null);
  const [filterOptions, setFilterOptions] = useState({
    actions: [],
    entityTypes: []
  });
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');
  const [pagination, setPagination] = useState({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0
  });
  
  const [filters, setFilters] = useState({
    action: '',
    entity_type: '',
    date_from: '',
    date_to: ''
  });
  
  // Fetch activity logs
  useEffect(() => {
    const fetchLogs = async () => {
      setIsLoading(true);
      try {
        // Create params object for filtering
        const params = { page: pagination.currentPage };
        Object.entries(filters).forEach(([key, value]) => {
          if (value) params[key] = value;
        });
        
        const response = await axios.get('/activity-logs', { params });
        setLogs(response.data.data);
        setPagination({
          currentPage: response.data.current_page,
          lastPage: response.data.last_page,
          perPage: response.data.per_page,
          total: response.data.total
        });
      } catch (err) {
        setError('Failed to load activity logs. Please try again.');
        console.error('Error loading logs:', err);
      } finally {
        setIsLoading(false);
      }
    };

    fetchLogs();
  }, [filters, pagination.currentPage]);
  
  // Fetch summary and filter options
  useEffect(() => {
    const fetchSummaryAndFilters = async () => {
      try {
        // Fetch summary data
        const summaryResponse = await axios.get('/activity-logs/summary');
        setSummary(summaryResponse.data);
        
        // Fetch filter options
        const filterResponse = await axios.get('/activity-logs/filters');
        setFilterOptions({
          actions: filterResponse.data.actions,
          entityTypes: filterResponse.data.entity_types
        });
      } catch (err) {
        console.error('Error loading summary or filters:', err);
      }
    };
    
    fetchSummaryAndFilters();
  }, []);
  
  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({
      ...prev,
      [name]: value
    }));
    // Reset to page 1 when filters change
    if (pagination.currentPage !== 1) {
      setPagination(prev => ({
        ...prev,
        currentPage: 1
      }));
    }
  };
  
  const clearFilters = () => {
    setFilters({
      action: '',
      entity_type: '',
      date_from: '',
      date_to: ''
    });
    setPagination(prev => ({
      ...prev,
      currentPage: 1
    }));
  };
  
  const handlePageChange = (page) => {
    if (page >= 1 && page <= pagination.lastPage) {
      setPagination(prev => ({
        ...prev,
        currentPage: page
      }));
    }
  };
  
  // Format date for display
  const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString();
  };
  
  // Get icon and color class based on action
  const getActionInfo = (action) => {
    switch (action) {
      case 'create':
        return { icon: '➕', className: 'action-create' };
      case 'update':
        return { icon: '✏️', className: 'action-update' };
      case 'delete':
        return { icon: '🗑️', className: 'action-delete' };
      case 'login':
        return { icon: '🔑', className: 'action-login' };
      case 'logout':
        return { icon: '🚪', className: 'action-logout' };
      default:
        return { icon: '🔄', className: 'action-default' };
    }
  };
  
  // Get icon and color class based on entity type
  const getEntityTypeInfo = (entityType) => {
    switch (entityType) {
      case 'post':
        return { icon: '📝', className: 'entity-post' };
      case 'platform':
        return { icon: '📱', className: 'entity-platform' };
      case 'profile':
        return { icon: '👤', className: 'entity-profile' };
      case 'platform_settings':
        return { icon: '⚙️', className: 'entity-settings' };
      default:
        return { icon: '📄', className: 'entity-default' };
    }
  };
  
  // Generate pagination controls
  const renderPagination = () => {
    const { currentPage, lastPage } = pagination;
    
    // Don't show pagination if only one page
    if (lastPage <= 1) return null;
    
    // Create an array of page numbers to display
    let pages = [];
    
    // Always show first page
    pages.push(1);
    
    // Calculate range around current page
    const range = 2; // Show 2 pages before and after current
    const startPage = Math.max(2, currentPage - range);
    const endPage = Math.min(lastPage - 1, currentPage + range);
    
    // Add ellipsis after first page if needed
    if (startPage > 2) {
      pages.push('...');
    }
    
    // Add pages in range
    for (let i = startPage; i <= endPage; i++) {
      pages.push(i);
    }
    
    // Add ellipsis before last page if needed
    if (endPage < lastPage - 1) {
      pages.push('...');
    }
    
    // Always show last page if more than one page
    if (lastPage > 1) {
      pages.push(lastPage);
    }
    
    return (
      <div className="pagination">
        <button 
          onClick={() => handlePageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className="pagination-btn"
        >
          &laquo; Previous
        </button>
        
        <div className="page-numbers">
          {pages.map((page, index) => (
            <button 
              key={index}
              onClick={() => typeof page === 'number' ? handlePageChange(page) : null}
              className={`page-number ${currentPage === page ? 'active' : ''} ${page === '...' ? 'ellipsis' : ''}`}
              disabled={page === '...'}
            >
              {page}
            </button>
          ))}
        </div>
        
        <button 
          onClick={() => handlePageChange(currentPage + 1)}
          disabled={currentPage === lastPage}
          className="pagination-btn"
        >
          Next &raquo;
        </button>
      </div>
    );
  };
  
  return (
    <div className="activity-log-container">
      <h1>Activity Log</h1>
      
      {/* Summary Cards */}
      {summary && (
        <div className="activity-summary">
          <div className="summary-card">
            <h3>Total Activities</h3>
            <div className="summary-value">{summary.total_count}</div>
          </div>
          
          <div className="summary-card">
            <h3>Recent Activities</h3>
            <div className="summary-value">{summary.recent_count}</div>
            <div className="summary-label">Last 7 days</div>
          </div>
          
          {summary.action_counts.login && (
            <div className="summary-card">
              <h3>Logins</h3>
              <div className="summary-value">{summary.action_counts.login}</div>
            </div>
          )}
        </div>
      )}
      
      {/* Filters */}
      <div className="filters-container">
        <h3>Filter Activity Logs</h3>
        <div className="filters-grid">
          <div className="filter-group">
            <label htmlFor="action">Action:</label>
            <select 
              id="action" 
              name="action" 
              value={filters.action}
              onChange={handleFilterChange}
            >
              <option value="">All Actions</option>
              {filterOptions.actions.map(action => (
                <option key={action} value={action}>{action}</option>
              ))}
            </select>
          </div>
          
          <div className="filter-group">
            <label htmlFor="entity_type">Entity Type:</label>
            <select 
              id="entity_type" 
              name="entity_type" 
              value={filters.entity_type}
              onChange={handleFilterChange}
            >
              <option value="">All Types</option>
              {filterOptions.entityTypes.map(type => (
                <option key={type} value={type}>{type}</option>
              ))}
            </select>
          </div>
          
          <div className="filter-group">
            <label htmlFor="date_from">From Date:</label>
            <input 
              type="date" 
              id="date_from" 
              name="date_from"
              value={filters.date_from}
              onChange={handleFilterChange}
            />
          </div>
          
          <div className="filter-group">
            <label htmlFor="date_to">To Date:</label>
            <input 
              type="date" 
              id="date_to" 
              name="date_to"
              value={filters.date_to}
              onChange={handleFilterChange}
            />
          </div>
        </div>
        
        <button 
          className="clear-filters-btn"
          onClick={clearFilters}
        >
          Clear Filters
        </button>
      </div>
      
      {/* Error message */}
      {error && <div className="error-message">{error}</div>}
      
      {/* Activity logs table */}
      {isLoading ? (
        <div className="loading">Loading activity logs...</div>
      ) : logs.length > 0 ? (
        <div className="activity-logs">
          <table className="activity-table">
            <thead>
              <tr>
                <th>Action</th>
                <th>Description</th>
                <th>Entity</th>
                <th>Date & Time</th>
              </tr>
            </thead>
            <tbody>
              {logs.map(log => {
                const actionInfo = getActionInfo(log.action);
                const entityInfo = log.entity_type ? getEntityTypeInfo(log.entity_type) : { icon: '', className: '' };
                
                return (
                  <tr key={log.id}>
                    <td>
                      <div className={`action-cell ${actionInfo.className}`}>
                        <span className="action-icon">{actionInfo.icon}</span>
                        <span className="action-name">{log.action}</span>
                      </div>
                    </td>
                    <td>{log.description}</td>
                    <td>
                      {log.entity_type && (
                        <div className={`entity-cell ${entityInfo.className}`}>
                          <span className="entity-icon">{entityInfo.icon}</span>
                          <span className="entity-name">{log.entity_type}</span>
                          {log.entity_id && <span className="entity-id">#{log.entity_id}</span>}
                        </div>
                      )}
                    </td>
                    <td>{formatDate(log.created_at)}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
          
          {/* Pagination */}
          {renderPagination()}
        </div>
      ) : (
        <div className="no-logs">
          <p>No activity logs found. {filters.action || filters.entity_type || filters.date_from || filters.date_to ? 'Try changing your filters.' : ''}</p>
        </div>
      )}
    </div>
  );
}

export default ActivityLog; 