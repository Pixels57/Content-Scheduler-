import React, { useState, useEffect } from 'react';
import axios from '../api/axios';
import '../styles/PlatformSettings.css';

function PlatformSettings() {
  const [platforms, setPlatforms] = useState([]);
  const [selectedPlatforms, setSelectedPlatforms] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [isCreating, setIsCreating] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [showAddForm, setShowAddForm] = useState(false);
  const [newPlatform, setNewPlatform] = useState({
    name: '',
    type: '',
    character_limit: 280 // Default character limit
  });
  
  const fetchPlatforms = async () => {
    try {
      // Fetch all available platforms
      console.log('Fetching platforms...');
      const platformsResponse = await axios.get('/platforms');
      console.log('Platforms response:', platformsResponse);
      
      if (platformsResponse.data && Array.isArray(platformsResponse.data.data)) {
        // Add tempStatus to each platform matching its actual status
        const platformsWithTempStatus = platformsResponse.data.data.map(platform => ({
          ...platform,
          tempStatus: platform.status
        }));
        
        setPlatforms(platformsWithTempStatus);
        console.log('Platforms set:', platformsWithTempStatus);
      } else if (platformsResponse.data && typeof platformsResponse.data === 'object') {
        // Handle case where response might be direct object instead of {data: [...]}
        const platformsArray = Array.isArray(platformsResponse.data) 
          ? platformsResponse.data.map(platform => ({
              ...platform,
              tempStatus: platform.status
            }))
          : [];
        setPlatforms(platformsArray);
        console.log('Platforms set (alternative format):', platformsArray);
      } else {
        console.error('Invalid platforms data format:', platformsResponse.data);
        setPlatforms([]);
        setError('Received invalid platform data from the server.');
      }
      
      // Fetch user profile to get active platforms
      const profileResponse = await axios.get('/profile');
      console.log('Profile response:', profileResponse);
      
      // Safely access the platforms array with a null check
      const userPlatforms = profileResponse.data.user?.platforms || [];
      console.log('User platforms:', userPlatforms);
      
      if (userPlatforms.length > 0) {
        setSelectedPlatforms(
          userPlatforms.map(platform => platform.id)
        );
        console.log('Selected platforms set:', userPlatforms.map(platform => platform.id));
      }
    } catch (err) {
      console.error('Error details:', err.response || err);
      setError(
        err.response?.data?.message || 
        'Failed to load platform data. Please try again.'
      );
      console.error('Error loading platform data:', err);
    } finally {
      setIsLoading(false);
    }
  };
  
  useEffect(() => {
    setIsLoading(true);
    fetchPlatforms();
  }, []);
  
  const handleTogglePlatform = (platformId) => {
    // Update selected platforms array
    setSelectedPlatforms(prev => {
      const isSelected = prev.includes(platformId);
      
      // If platform is currently selected, remove it
      if (isSelected) {
        return prev.filter(id => id !== platformId);
      } 
      // If platform is not currently selected, add it
      else {
        return [...prev, platformId];
      }
    });
    
    // Also update the visual status immediately in the UI
    // This doesn't affect the actual status in the database until saved
    setPlatforms(prev => 
      prev.map(platform => {
        if (platform.id === platformId) {
          return {
            ...platform,
            tempStatus: platform.tempStatus === 'active' ? 'inactive' : 'active'
          };
        }
        return platform;
      })
    );
  };
  
  const handleSaveSettings = async () => {
    // Don't attempt to save if no platforms are selected
    if (!selectedPlatforms.length) {
      setError('Please select at least one platform before saving.');
      return;
    }
    
    setIsSaving(true);
    setError('');
    setSuccessMessage('');
    
    try {
      console.log('Sending platform_ids:', selectedPlatforms);
      const response = await axios.post('/platforms/toggle', {
        platform_ids: selectedPlatforms
      });
      
      // Update the local platforms with the updated statuses
      if (response.data && response.data.platforms) {
        // Update platforms with the fresh data from the server
        // and synchronize tempStatus with the actual status
        const updatedPlatforms = response.data.platforms.map(platform => ({
          ...platform,
          tempStatus: platform.status
        }));
        setPlatforms(updatedPlatforms);
        
        // Reset selected platforms to uncheck all checkboxes
        setSelectedPlatforms([]);
      } else {
        // If the response doesn't include platforms, fetch them again
        await fetchPlatforms();
        
        // Reset selected platforms to uncheck all checkboxes
        setSelectedPlatforms([]);
      }
      
      setSuccessMessage('Platform settings updated successfully!');
      
      // Clear success message after 3 seconds
      setTimeout(() => {
        setSuccessMessage('');
      }, 3000);
    } catch (err) {
      console.error('Error response:', err.response?.data);
      setError(
        err.response?.data?.message || 
        'Failed to update platform settings. Please try again.'
      );
      console.error('Error updating platform settings:', err);
    } finally {
      setIsSaving(false);
    }
  };
  
  const handleDeletePlatform = async (platformId) => {
    if (!window.confirm('Are you sure you want to delete this platform? This action cannot be undone.')) {
      return;
    }
    
    setIsDeleting(true);
    setError('');
    
    try {
      await axios.delete(`/platforms/${platformId}`);
      // Refresh platforms list
      await fetchPlatforms();
      setSuccessMessage('Platform deleted successfully!');
      
      // Clear success message after 3 seconds
      setTimeout(() => {
        setSuccessMessage('');
      }, 3000);
    } catch (err) {
      setError(
        err.response?.data?.message || 
        'Failed to delete platform. Please try again.'
      );
      console.error('Error deleting platform:', err);
    } finally {
      setIsDeleting(false);
    }
  };
  
  const handleNewPlatformChange = (e) => {
    const { name, value } = e.target;
    setNewPlatform(prev => ({
      ...prev,
      [name]: name === 'character_limit' ? parseInt(value, 10) : value
    }));
  };
  
  const handleCreatePlatform = async (e) => {
    e.preventDefault();
    
    if (!newPlatform.name || !newPlatform.type) {
      setError('Platform name and type are required.');
      return;
    }
    
    setIsCreating(true);
    setError('');
    
    try {
      await axios.post('/platforms', newPlatform);
      
      // Reset form
      setNewPlatform({
        name: '',
        type: '',
        character_limit: 280
      });
      
      // Close the form
      setShowAddForm(false);
      
      // Refresh platforms list
      await fetchPlatforms();
      
      setSuccessMessage('Platform created successfully!');
      
      // Clear success message after 3 seconds
      setTimeout(() => {
        setSuccessMessage('');
      }, 3000);
    } catch (err) {
      console.error('Error details:', err.response || err);
      
      // Handle validation errors specifically
      if (err.response?.status === 422 && err.response?.data?.errors) {
        const validationErrors = err.response.data.errors;
        if (validationErrors.name && validationErrors.name.includes('The name has already been taken.')) {
          setError(`Platform name "${newPlatform.name}" already exists. Please use a different name.`);
        } else {
          setError(
            Object.values(validationErrors).flat().join(' ') || 
            'Failed to create platform. Please check your inputs and try again.'
          );
        }
      } else {
        setError(
          err.response?.data?.message || 
          'Failed to create platform. Please try again.'
        );
      }
      
      console.error('Error creating platform:', err);
    } finally {
      setIsCreating(false);
    }
  };
  
  if (isLoading) {
    return <div className="loading">Loading platform settings...</div>;
  }
  
  return (
    <div className="platform-settings">
      <h1>Platform Settings</h1>
      
      <div className="settings-actions">
        <button 
          className="btn-add-platform"
          onClick={() => setShowAddForm(!showAddForm)}
        >
          {showAddForm ? 'Cancel' : 'Add New Platform'}
        </button>
      </div>
      
      {showAddForm && (
        <div className="add-platform-form">
          <h2>Add New Platform</h2>
          <form onSubmit={handleCreatePlatform}>
            <div className="form-group">
              <label htmlFor="name">Platform Name</label>
              <input
                type="text"
                id="name"
                name="name"
                value={newPlatform.name}
                onChange={handleNewPlatformChange}
                required
                placeholder="e.g. Twitter, Instagram, LinkedIn"
              />
            </div>
            
            <div className="form-group">
              <label htmlFor="type">Platform Type</label>
              <input
                type="text"
                id="type"
                name="type"
                value={newPlatform.type}
                onChange={handleNewPlatformChange}
                required
                placeholder="e.g. Social Media, Blog"
              />
            </div>
            
            <div className="form-group">
              <label htmlFor="character_limit">Character Limit</label>
              <input
                type="number"
                id="character_limit"
                name="character_limit"
                value={newPlatform.character_limit}
                onChange={handleNewPlatformChange}
                min="1"
                required
              />
              <small className="input-help">Maximum characters allowed in a post</small>
            </div>
            
            <button 
              type="submit" 
              className="btn-create"
              disabled={isCreating}
            >
              {isCreating ? 'Creating...' : 'Create Platform'}
            </button>
          </form>
        </div>
      )}
      
      <p className="platform-description">
        Select the platforms you want to use for your content. 
        These platforms will be available when creating or editing posts.
      </p>
      
      <p className="platform-instructions">
        <strong>How to use:</strong> Check the boxes for platforms you want to toggle status (active/inactive).
        After saving, all checkboxes will be cleared and platform statuses will be updated.
      </p>
      
      {error && <div className="error-message">{error}</div>}
      {successMessage && <div className="success-message">{successMessage}</div>}
      
      <div className="platform-list">
        {!platforms || platforms.length === 0 ? (
          <div className="no-platforms">No platforms available.</div>
        ) : (
          platforms.map(platform => (
            <div 
              key={platform.id} 
              className={`platform-item ${(platform.tempStatus || platform.status) === 'inactive' ? 'platform-inactive' : ''}`}
            >
              <div className="platform-content">
                <label className="platform-label">
                  <input
                    type="checkbox"
                    checked={selectedPlatforms.includes(platform.id)}
                    onChange={() => handleTogglePlatform(platform.id)}
                  />
                  <div className="platform-info">
                    <span className="platform-name">{platform.name}</span>
                    <span className="platform-type">{platform.type}</span>
                    <span className="platform-limit">Limit: {platform.character_limit} chars</span>
                    <span className={`platform-status ${platform.tempStatus || platform.status}`}>
                      Status: {(platform.tempStatus || platform.status) === 'active' ? 'Active' : 'Inactive'}
                    </span>
                  </div>
                </label>
              </div>
              <div className="platform-actions">
                <button 
                  className="btn-delete"
                  onClick={() => handleDeletePlatform(platform.id)}
                  disabled={isDeleting}
                >
                  Delete
                </button>
              </div>
            </div>
          ))
        )}
      </div>
      
      <button
        className="btn-save"
        onClick={handleSaveSettings}
        disabled={isSaving || !platforms || platforms.length === 0}
      >
        {isSaving ? 'Saving...' : 'Save Platform Settings'}
      </button>
    </div>
  );
}

export default PlatformSettings; 