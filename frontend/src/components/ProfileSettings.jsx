import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from '../api/axios';
import '../styles/ProfileSettings.css';

function ProfileSettings() {
  const navigate = useNavigate();
  const [user, setUser] = useState({
    name: '',
    email: ''
  });
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
  });
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    const fetchUserProfile = async () => {
      try {
        const response = await axios.get('/profile');
        const userData = response.data.user;
        
        setUser(userData);
        setFormData({
          name: userData.name,
          email: userData.email,
          password: '',
          password_confirmation: ''
        });
      } catch (err) {
        setError('Failed to load profile. Please try again.');
        console.error('Error loading profile:', err);
      } finally {
        setIsLoading(false);
      }
    };

    fetchUserProfile();
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccessMessage('');
    setIsSaving(true);

    // Prepare data for API
    const updateData = {};
    
    // Only include fields that were changed
    if (formData.name !== user.name) {
      updateData.name = formData.name;
    }
    
    if (formData.email !== user.email) {
      updateData.email = formData.email;
    }
    
    if (formData.password) {
      updateData.password = formData.password;
      updateData.password_confirmation = formData.password_confirmation;
    }

    // Don't make API call if nothing changed
    if (Object.keys(updateData).length === 0) {
      setIsSaving(false);
      setSuccessMessage('No changes to save');
      return;
    }

    try {
      const response = await axios.put('/profile', updateData);
      
      // Update local user state with the response
      setUser(response.data.user);
      setSuccessMessage('Profile updated successfully');
      
      // Clear password fields after successful update
      setFormData({
        ...formData,
        password: '',
        password_confirmation: ''
      });
      
      // Show success message briefly before logout
      setTimeout(() => {
        // Log the user out
        logoutAndRedirect();
      }, 1500);
      
    } catch (err) {
      setError(
        err.response?.data?.message || 
        'Failed to update profile. Please try again.'
      );
      console.error('Error updating profile:', err);
    } finally {
      setIsSaving(false);
    }
  };
  
  const logoutAndRedirect = async () => {
    try {
      // Call logout API
      await axios.post('/logout');
      
      // Remove token from local storage
      localStorage.removeItem('token');
      
      // Redirect to login page
      navigate('/login');
    } catch (error) {
      console.error('Logout failed:', error);
      // If logout fails, still try to redirect
      navigate('/login');
    }
  };

  if (isLoading) {
    return <div className="loading">Loading profile...</div>;
  }

  return (
    <div className="profile-settings">
      <h1>Profile Settings</h1>
      
      {error && <div className="error-message">{error}</div>}
      {successMessage && <div className="success-message">{successMessage}</div>}
      
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="name">Name</label>
          <input
            type="text"
            id="name"
            name="name"
            value={formData.name}
            onChange={handleChange}
            required
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="email">Email</label>
          <input
            type="email"
            id="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            required
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="password">
            New Password <span className="optional">(leave blank to keep current)</span>
          </label>
          <input
            type="password"
            id="password"
            name="password"
            value={formData.password}
            onChange={handleChange}
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="password_confirmation">Confirm New Password</label>
          <input
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            value={formData.password_confirmation}
            onChange={handleChange}
          />
        </div>
        
        <div className="form-info">
          <p className="note">Note: After saving changes, you will be logged out and redirected to the login page.</p>
        </div>
        
        <button 
          type="submit" 
          className="btn-save"
          disabled={isSaving}
        >
          {isSaving ? 'Saving...' : 'Save Changes'}
        </button>
      </form>
    </div>
  );
}

export default ProfileSettings; 