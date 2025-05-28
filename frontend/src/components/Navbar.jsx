import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import axios from '../api/axios';
import '../styles/Navbar.css';

function Navbar() {
  const navigate = useNavigate();

  const handleLogout = async () => {
    try {
      await axios.post('/logout');
      localStorage.removeItem('token');
      navigate('/login');
    } catch (error) {
      console.error('Logout failed:', error);
    }
  };

  return (
    <nav className="navbar">
      <div className="navbar-brand">
        <Link to="/dashboard">Content Scheduler</Link>
      </div>
      <div className="navbar-menu">
        <Link to="/dashboard" className="navbar-item">Dashboard</Link>
        <Link to="/posts/new" className="navbar-item">New Post</Link>
        <Link to="/settings/platforms" className="navbar-item">Platforms</Link>
        <button onClick={handleLogout} className="navbar-item logout-btn">
          Logout
        </button>
      </div>
    </nav>
  );
}

export default Navbar; 