import api from './api';

/**
 * Service d'authentification
 * Gère les appels API pour login, register, logout
 */
const authService = {
  /**
   * Connexion utilisateur
   * @param {string} email
   * @param {string} password
   * @returns {Promise}
   */
  login: async (email, password) => {
    try {
      const response = await api.post('/login', { email, password });
      
      if (response.data.token) {
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
      }
      
      return response.data;
    } catch (error) {
      throw error.response?.data || { message: 'Erreur de connexion' };
    }
  },

  /**
   * Inscription utilisateur
   * @param {Object} userData - Données de l'utilisateur
   * @returns {Promise}
   */
  register: async (userData) => {
    try {
      const response = await api.post('/register', userData);
      
      // Optionnel : connecter automatiquement l'utilisateur après inscription
      if (response.data.token) {
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
      }
      
      return response.data;
    } catch (error) {
      throw error.response?.data || { message: 'Erreur lors de l\'inscription' };
    }
  },

  /**
   * Déconnexion utilisateur
   */
  logout: async () => {
    try {
      await api.post('/logout');
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error);
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
  },

  /**
   * Récupérer l'utilisateur connecté
   * @returns {Object|null}
   */
  getCurrentUser: () => {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  },

  /**
   * Vérifier si l'utilisateur est connecté
   * @returns {boolean}
   */
  isAuthenticated: () => {
    return !!localStorage.getItem('token');
  },

  /**
   * Rafraîchir le token JWT
   * @returns {Promise}
   */
  refreshToken: async () => {
    try {
      const response = await api.post('/token/refresh');
      
      if (response.data.token) {
        localStorage.setItem('token', response.data.token);
      }
      
      return response.data;
    } catch (error) {
      throw error.response?.data || { message: 'Erreur lors du rafraîchissement du token' };
    }
  },

  /**
   * Demande de réinitialisation de mot de passe
   * @param {string} email
   * @returns {Promise}
   */
  forgotPassword: async (email) => {
    try {
      const response = await api.post('/forgot-password', { email });
      return response.data;
    } catch (error) {
      throw error.response?.data || { message: 'Erreur lors de la demande' };
    }
  },

  /**
   * Réinitialisation du mot de passe
   * @param {string} token
   * @param {string} password
   * @returns {Promise}
   */
  resetPassword: async (token, password) => {
    try {
      const response = await api.post('/reset-password', { token, password });
      return response.data;
    } catch (error) {
      throw error.response?.data || { message: 'Erreur lors de la réinitialisation' };
    }
  }
};

export default authService;

