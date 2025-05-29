import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axios from '../api/axios';
import '../styles/PostEditor.css';

function PostEditor() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [platforms, setPlatforms] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [formData, setFormData] = useState({
    title: '',
    content: '',
    image_url: '',
    scheduled_time: new Date().toISOString().slice(0, 16), // Format: YYYY-MM-DDThh:mm
    status: 'draft',
    platform_ids: []
  });
  
  // Platform character limits
  const platformLimits = {
    twitter: 280,
    instagram: 2200,
    linkedin: 3000,
    facebook: 63206
  };
  
  // Get the lowest character limit from selected platforms
  const getCharacterLimit = () => {
    if (formData.platform_ids.length === 0) {
      return Infinity;
    }
    
    let lowestLimit = Infinity;
    let limitingPlatform = '';
    
    for (const platformId of formData.platform_ids) {
      const platform = platforms.find(p => p.id === platformId);
      if (platform && platformLimits[platform.type] && platformLimits[platform.type] < lowestLimit) {
        lowestLimit = platformLimits[platform.type];
        limitingPlatform = platform.name;
      }
    }
    
    return {
      limit: lowestLimit === Infinity ? null : lowestLimit,
      platform: limitingPlatform
    };
  };
  
  // Check if content exceeds character limit
  const isOverCharacterLimit = () => {
    const { limit } = getCharacterLimit();
    return limit ? formData.content.length > limit : false;
  };
  
  // Get remaining characters
  const getRemainingCharacters = () => {
    const { limit } = getCharacterLimit();
    return limit ? limit - formData.content.length : null;
  };
  
  // Fetch available platforms and post data if editing
  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        // Fetch platforms
        const platformsResponse = await axios.get('/platforms');
        setPlatforms(platformsResponse.data.data);
        
        // Fetch post data if editing an existing post
        if (id) {
          const postResponse = await axios.get(`/posts/${id}`);
          const post = postResponse.data.data;
          
          // Format the date-time for the input
          const scheduledTime = new Date(post.scheduled_time)
            .toISOString()
            .slice(0, 16);
          
          setFormData({
            title: post.title || '',
            content: post.content || '',
            image_url: post.image_url || '',
            scheduled_time: scheduledTime,
            status: post.status || 'draft',
            platform_ids: post.platforms.map(p => p.id) || []
          });
        }
      } catch (err) {
        setError('Failed to load data. Please try again.');
        console.error('Error loading data:', err);
      } finally {
        setIsLoading(false);
      }
    };
    
    fetchData();
  }, [id]);
  
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };
  
  const handlePlatformToggle = (platformId) => {
    // Find the platform
    const platform = platforms.find(p => p.id === platformId);
    
    // Only allow toggling if platform is active
    if (platform && platform.status === 'active') {
      // Special handling for Instagram
      if (platform.type === 'instagram') {
        // If trying to enable Instagram but no image is provided
        if (!formData.platform_ids.includes(platformId) && !formData.image_url) {
          // Show warning and ask user to confirm
          const wantsToProceed = window.confirm(
            'Instagram posts require an image. Do you want to select Instagram now and add an image later?'
          );
          
          if (!wantsToProceed) {
            return; // Don't add Instagram if user cancels
          }
          
          // Highlight the image upload section
          setTimeout(() => {
            const imageElement = document.getElementById('image');
            if (imageElement) {
              imageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          }, 300);
        }
      }
      
      // Toggle the platform selection
      setFormData(prev => {
        const newPlatformIds = prev.platform_ids.includes(platformId)
          ? prev.platform_ids.filter(id => id !== platformId)
          : [...prev.platform_ids, platformId];
          
        return {
          ...prev,
          platform_ids: newPlatformIds
        };
      });
    }
  };
  
  // Check if platform is inactive
  const isPlatformInactive = (platform) => {
    return platform.status === 'inactive';
  };
  
  const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    // Check file size (limit to 5MB)
    if (file.size > 5 * 1024 * 1024) {
      setError('Image size should be less than 5MB');
      return;
    }
    
    // Check file type
    if (!file.type.match('image.*')) {
      setError('Please select an image file');
      return;
    }
    
    // Convert to base64
    const reader = new FileReader();
    reader.onload = (event) => {
      setFormData(prev => ({
        ...prev,
        image_url: event.target.result
      }));
    };
    reader.readAsDataURL(file);
  };
  
  const validateForm = () => {
    // Check character limit
    if (isOverCharacterLimit()) {
      const { limit, platform } = getCharacterLimit();
      setError(`Content exceeds the character limit (${limit}) for ${platform}`);
      return false;
    }
    
    // Check if Instagram is selected but no image is uploaded
    const instagramPlatform = platforms.find(p => p.type === 'instagram');
    if (instagramPlatform && 
        formData.platform_ids.includes(instagramPlatform.id) && 
        !formData.image_url) {
      setError('Instagram posts require an image. Please upload an image.');
      return false;
    }
    
    // If scheduled status is selected, scheduled_time must be set and in the future
    if (formData.status === 'scheduled') {
      const now = new Date();
      const scheduledTime = new Date(formData.scheduled_time);
      
      if (!formData.scheduled_time) {
        setError('Please set a schedule time for scheduled posts');
        return false;
      }
      
      if (scheduledTime <= now) {
        setError('Schedule time must be in the future');
        return false;
      }
    }
    
    return true;
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    
    // Validate form
    if (!validateForm()) {
      return;
    }
    
    setIsLoading(true);
    
    // If no status is explicitly selected, set to published
    const submissionData = { ...formData };
    if (!submissionData.status) {
      submissionData.status = 'published';
    }
    
    try {
      if (id) {
        await axios.put(`/posts/${id}`, submissionData);
      } else {
        await axios.post('/posts', submissionData);
      }
      navigate('/dashboard');
    } catch (err) {
      console.error('Error saving post:', err);
      
      // Handle validation errors from backend
      if (err.response?.data?.errors) {
        const errors = err.response.data.errors;
        
        // Check for Instagram-specific image validation error
        if (errors.image_url && errors.image_url.includes('Instagram')) {
          setError('Instagram posts require an image. Please upload an image.');
          
          // Scroll to the image upload section
          document.getElementById('image').scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
          // Handle other validation errors
          const errorMessage = Object.values(errors)
            .flat()
            .join(' ');
          setError(errorMessage || 'Validation failed. Please check your inputs.');
        }
      } else {
        // Handle generic errors
        setError(
          err.response?.data?.message || 
          'Failed to save post. Please check your inputs and try again.'
        );
      }
    } finally {
      setIsLoading(false);
    }
  };
  
  // Function to determine if scheduled_time field should be disabled
  const isScheduledTimeDisabled = () => {
    return formData.status !== 'scheduled';
  };
  
  if (isLoading && !formData.title) {
    return <div className="loading">Loading...</div>;
  }
  
  // Get character limit info
  const characterLimitInfo = getCharacterLimit();
  const remainingCharacters = getRemainingCharacters();
  
  return (
    <div className="post-editor">
      <h1>{id ? 'Edit Post' : 'Create New Post'}</h1>
      
      {error && <div className="error-message">{error}</div>}
      
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="title">Title</label>
          <input
            type="text"
            id="title"
            name="title"
            value={formData.title}
            onChange={handleChange}
            required
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="content">Content</label>
          <textarea
            id="content"
            name="content"
            value={formData.content}
            onChange={handleChange}
            required
            rows="6"
          />
          <div className={`character-counter ${isOverCharacterLimit() ? 'over-limit' : ''}`}>
            {formData.content.length} characters
            {characterLimitInfo.limit && (
              <>
                <span className="character-limit-info">
                  {remainingCharacters >= 0 
                    ? `${remainingCharacters} remaining` 
                    : `${Math.abs(remainingCharacters)} over limit!`}
                </span>
                <span className="character-limit-platform">
                  Limit: {characterLimitInfo.limit} ({characterLimitInfo.platform})
                </span>
              </>
            )}
          </div>
        </div>
        
        <div className="form-group">
          <label htmlFor="image">
            Image Upload
            {formData.platform_ids.includes(platforms.find(p => p.type === 'instagram')?.id) && (
              <span className="required-badge">Required for Instagram</span>
            )}
          </label>
          <div className={`image-upload-container ${formData.platform_ids.includes(platforms.find(p => p.type === 'instagram')?.id) && !formData.image_url ? 'instagram-required' : ''}`}>
            <input
              type="file"
              id="image"
              name="image"
              accept="image/*"
              onChange={handleImageUpload}
              className="image-upload-input"
            />
            <label htmlFor="image" className={`image-upload-button ${formData.platform_ids.includes(platforms.find(p => p.type === 'instagram')?.id) && !formData.image_url ? 'instagram-required-button' : ''}`}>
              {formData.platform_ids.includes(platforms.find(p => p.type === 'instagram')?.id) && !formData.image_url ? 'Choose Image (Required for Instagram)' : 'Choose Image'}
            </label>
            {formData.image_url && (
              <button 
                type="button" 
                className="clear-image-button"
                onClick={() => {
                  // Add confirmation if Instagram is selected
                  const instagramPlatform = platforms.find(p => p.type === 'instagram');
                  if (instagramPlatform && formData.platform_ids.includes(instagramPlatform.id)) {
                    if (window.confirm('Removing this image will prevent posting to Instagram. Continue?')) {
                      setFormData(prev => ({ ...prev, image_url: '' }));
                    }
                  } else {
                    setFormData(prev => ({ ...prev, image_url: '' }));
                  }
                }}
              >
                Clear Image
              </button>
            )}
          </div>
          {formData.image_url && (
            <div className="image-preview">
              <img 
                src={formData.image_url} 
                alt="Preview" 
                onError={(e) => {
                  console.error("Error loading image:", e);
                  e.target.src = "https://via.placeholder.com/300x200?text=Image+Load+Error";
                  e.target.style.opacity = "0.5";
                }}
              />
              {formData.image_url.startsWith('http') && (
                <div className="cloudinary-info">
                  <span className="cloudinary-badge">Cloudinary Image</span>
                  <a 
                    href={formData.image_url} 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    className="view-original"
                  >
                    View Original
                  </a>
                </div>
              )}
            </div>
          )}
          {formData.platform_ids.includes(platforms.find(p => p.type === 'instagram')?.id) && !formData.image_url && (
            <div className="platform-warning">
              ⚠️ Instagram posts require an image - Please upload one now!
            </div>
          )}
        </div>
        
        <div className="form-group">
          <label>Platforms</label>
          <div className="platform-selector">
            {platforms.map(platform => (
              <label 
                key={platform.id} 
                className={`platform-option ${isPlatformInactive(platform) ? 'platform-inactive' : ''}`}
              >
                <input
                  type="checkbox"
                  checked={formData.platform_ids.includes(platform.id)}
                  onChange={() => handlePlatformToggle(platform.id)}
                  disabled={isPlatformInactive(platform)}
                />
                <span>{platform.name}</span>
                {platformLimits[platform.type] && (
                  <span className="platform-limit">
                    ({platformLimits[platform.type]} chars)
                  </span>
                )}
                {platform.type === 'instagram' && (
                  <span className="platform-requirement">
                    Requires image
                  </span>
                )}
                {isPlatformInactive(platform) && (
                  <span className="platform-status-indicator">Inactive</span>
                )}
              </label>
            ))}
          </div>
          {platforms.some(p => p.status === 'inactive') && (
            <div className="platform-note">
              Inactive platforms are disabled and cannot be selected
            </div>
          )}
          {formData.platform_ids.includes(platforms.find(p => p.type === 'instagram')?.id) && !formData.image_url && (
            <div className="platform-warning">
              ⚠️ Instagram posts require an image - please upload one below
            </div>
          )}
        </div>
        
        <div className="form-group">
          <label>Publication Status</label>
          <div className="status-options">
            <label className="status-option">
              <input
                type="radio"
                name="status"
                value="draft"
                checked={formData.status === 'draft'}
                onChange={handleChange}
              />
              <span>Draft</span>
            </label>
            <label className="status-option">
              <input
                type="radio"
                name="status"
                value="scheduled"
                checked={formData.status === 'scheduled'}
                onChange={handleChange}
              />
              <span>Scheduled</span>
            </label>
            <label className="status-option">
              <input
                type="radio"
                name="status"
                value="published"
                checked={formData.status === 'published'}
                onChange={handleChange}
              />
              <span>Published</span>
            </label>
          </div>
        </div>
        
        <div className="form-group">
          <label htmlFor="scheduled_time">Schedule Time</label>
          <input
            type="datetime-local"
            id="scheduled_time"
            name="scheduled_time"
            value={formData.scheduled_time}
            onChange={handleChange}
            required={formData.status === 'scheduled'}
            disabled={isScheduledTimeDisabled()}
            className={isScheduledTimeDisabled() ? 'disabled' : ''}
          />
          {formData.status === 'scheduled' && (
            <div className="field-hint">
              Set the date and time when this post should be published.
              <div className="rate-limit-info">
                <strong>Note:</strong> You can schedule up to 10 posts per day.
              </div>
            </div>
          )}
        </div>
        
        <button 
          type="submit" 
          className="btn-submit"
          disabled={isLoading || isOverCharacterLimit()}
        >
          {isLoading ? 'Saving...' : (id ? 'Update Post' : 'Create Post')}
        </button>
      </form>
    </div>
  );
}

export default PostEditor; 