import React, { useState, useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import axios from './api/axios';

// Components
import Login from './components/Login';
import Register from './components/Register';
import Dashboard from './components/Dashboard';
import PostEditor from './components/PostEditor';
import PlatformSettings from './components/PlatformSettings';
import Navbar from './components/Navbar';

// Styles
import './App.css';

function App() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  
  useEffect(() => {
    // Check if user is logged in
    const checkAuth = async () => {
      const token = localStorage.getItem('token');
      
      if (!token) {
        setIsAuthenticated(false);
        setIsLoading(false);
        return;
      }
      
      try {
        await axios.get('/profile');
        setIsAuthenticated(true);
      } catch (error) {
        localStorage.removeItem('token');
        setIsAuthenticated(false);
      } finally {
        setIsLoading(false);
      }
    };
    
    checkAuth();
  }, []);
  
  if (isLoading) {
    return (
      <div className="app-loading">
        <div className="loading-spinner"></div>
        <p>Loading application...</p>
      </div>
    );
  }
  
  return (
    <BrowserRouter>
      <div className="app">
        {isAuthenticated && <Navbar />}
        
        <main className="app-content">
          <Routes>
            <Route 
              path="/login" 
              element={
                isAuthenticated ? 
                <Navigate to="/dashboard" /> : 
                <Login setIsAuthenticated={setIsAuthenticated} />
              } 
            />
            
            <Route 
              path="/register" 
              element={
                isAuthenticated ? 
                <Navigate to="/dashboard" /> : 
                <Register setIsAuthenticated={setIsAuthenticated} />
              } 
            />
            
            <Route 
              path="/dashboard" 
              element={
                isAuthenticated ? 
                <Dashboard /> : 
                <Navigate to="/login" />
              } 
            />
            
            <Route 
              path="/posts/new" 
              element={
                isAuthenticated ? 
                <PostEditor /> : 
                <Navigate to="/login" />
              } 
            />
            
            <Route 
              path="/posts/:id/edit" 
              element={
                isAuthenticated ? 
                <PostEditor /> : 
                <Navigate to="/login" />
              } 
            />
            
            <Route 
              path="/settings/platforms" 
              element={
                isAuthenticated ? 
                <PlatformSettings /> : 
                <Navigate to="/login" />
              } 
            />
            
            <Route 
              path="/" 
              element={
                <Navigate to={isAuthenticated ? "/dashboard" : "/login"} />
              } 
            />
          </Routes>
        </main>
      </div>
    </BrowserRouter>
  );
}

export default App;
