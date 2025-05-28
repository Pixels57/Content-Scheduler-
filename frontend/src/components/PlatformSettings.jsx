import React, { useState, useEffect } from 'react';
import axios from '../api/axios';
import '../styles/PlatformSettings.css';

function PlatformSettings() {
  const [platforms, setPlatforms] = useState([]);
  const [selectedPlatforms, setSelectedPlatforms] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  
  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        // Fetch all available platforms
        const platformsResponse = await axios.get('/platforms');
        setPlatforms(platformsResponse.data.data);
        
        // Fetch user profile to get active platforms
        const profileResponse = await axios.get('/profile');
        if (profileResponse.data.platforms) {
          setSelectedPlatforms(
            profileResponse.data.platforms.map(platform => platform.id)
          );
        }
      } catch (err) {
        setError('Failed to load platform data. Please try again.');
        console.error('Error loading platform data:', err);
      } finally {
        setIsLoading(false);
      }
    };
    
    fetchData();
  }, []);
  
  const handleTogglePlatform = (platformId) => {
    setSelectedPlatforms(prev => {
      if (prev.includes(platformId)) {
        return prev.filter(id => id !== platformId);
      } else {
        return [...prev, platformId];
      }
    });
  };
  
  const handleSaveSettings = async () => {
    setIsSaving(true);
    setError('');
    setSuccessMessage('');
    
    try {
      await axios.post('/platforms/toggle', {
        platform_ids: selectedPlatforms
      });
      setSuccessMessage('Platform settings updated successfully!');
      
      // Clear success message after 3 seconds
      setTimeout(() => {
        setSuccessMessage('');
      }, 3000);
    } catch (err) {
      setError(
        err.response?.data?.message || 
        'Failed to update platform settings. Please try again.'
      );
      console.error('Error updating platform settings:', err);
    } finally {
      setIsSaving(false);
    }
  };
  
  if (isLoading) {
    return <div className="loading">Loading platform settings...</div>;
  }
  
  return (
    <div className="platform-settings">
      <h1>Platform Settings</h1>
      
      <p className="platform-description">
        Select the platforms you want to use for your content. 
        These platforms will be available when creating or editing posts.
      </p>
      
      {error && <div className="error-message">{error}</div>}
      {successMessage && <div className="success-message">{successMessage}</div>}
      
      <div className="platform-list">
        {platforms.length === 0 ? (
          <div className="no-platforms">No platforms available.</div>
        ) : (
          platforms.map(platform => (
            <div key={platform.id} className="platform-item">
              <label className="platform-label">
                <input
                  type="checkbox"
                  checked={selectedPlatforms.includes(platform.id)}
                  onChange={() => handleTogglePlatform(platform.id)}
                />
                <div className="platform-info">
                  <span className="platform-name">{platform.name}</span>
                  <span className="platform-type">{platform.type}</span>
                </div>
              </label>
            </div>
          ))
        )}
      </div>
      
      <button
        className="btn-save"
        onClick={handleSaveSettings}
        disabled={isSaving}
      >
        {isSaving ? 'Saving...' : 'Save Platform Settings'}
      </button>
    </div>
  );
}

export default PlatformSettings; 