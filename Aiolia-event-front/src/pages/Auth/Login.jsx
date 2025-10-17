import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function Login() {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    rememberMe: false
  });
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const bannerData = {
    image: '/images/banner.jpg',
    subtitle: 'Événement à ne pas rater',
    title: 'Jazz show avec Koundé au "Le Grand Café de la Gare"',
    showTimer: true
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validateForm = () => {
    const newErrors = {};
    
    if (!formData.email) {
      newErrors.email = 'L\'email est requis';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email invalide';
    }
    
    if (!formData.password) {
      newErrors.password = 'Le mot de passe est requis';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) return;
    
    setIsLoading(true);
    
    try {
      // TODO: Connecter avec l'API Symfony
      console.log('Login data:', formData);
      
      // Simulation d'un appel API
      await new Promise(resolve => setTimeout(resolve, 1500));
      
      // TODO: Gérer le token JWT et rediriger
      alert('Connexion réussie ! (à connecter avec API)');
      navigate('/');
    } catch (error) {
      setErrors({ general: 'Erreur de connexion. Veuillez réessayer.' });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      <Header showBanner={true} bannerData={bannerData} />
      
      <main>
        <section className="sec-login pt-100 pb-100">
          <div className="container">
            <h2 className="text-center">Se connecter</h2>
            <div className="blc-login bg-beige">
              <form onSubmit={handleSubmit}>
                <div className="blc-chp">
                  <label>Votre adresse email</label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    className={`form-control ${errors.email ? 'error' : ''}`}
                  />
                  {errors.email && <div className="txt-error">{errors.email}</div>}
                </div>

                <div className="blc-chp">
                  <label>Votre mot de passe</label>
                  <div className="blc-password">
                    <input
                      type={showPassword ? 'text' : 'password'}
                      name="password"
                      value={formData.password}
                      onChange={handleChange}
                      className={`form-control ${errors.password ? 'error' : ''}`}
                      placeholder="****"
                    />
                    <span
                      className="material-symbols-rounded pointer toggle-password"
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? 'visibility_off' : 'visibility'}
                    </span>
                  </div>
                  {errors.password && <div className="txt-error">{errors.password}</div>}
                </div>

                <div className="blc-check d-flex justify-content-between">
                  <div className="check-connect">
                    <input
                      type="checkbox"
                      id="reste-connecte"
                      name="rememberMe"
                      checked={formData.rememberMe}
                      onChange={handleChange}
                    />
                    <label htmlFor="reste-connecte">Rester connecté</label>
                  </div>
                  <Link to="/forgot-password" className="pass-forgot">
                    J'ai oublié mon mot de passe
                  </Link>
                </div>

                {errors.general && (
                  <div className="txt-error text-center">{errors.general}</div>
                )}

                <div className="blc-btn text-center">
                  <input
                    type="submit"
                    className="btn-submit login"
                    name=""
                    value={isLoading ? 'Connexion en cours...' : 'se connecter'}
                    disabled={isLoading}
                  />
                  <Link to="/register" className="link-form">
                    Créer un nouveau compte
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </>
  );
}

export default Login;
