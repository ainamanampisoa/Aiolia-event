import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function Register() {
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    email: '',
    phoneCountryCode: '+261',
    phone: '',
    region: '',
    city: '',
    postalCode: '',
    password: '',
    confirmPassword: '',
    acceptTerms: false
  });
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);

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
    
    if (!formData.firstName.trim()) {
      newErrors.firstName = 'Le prénom est requis';
    }
    
    if (!formData.lastName.trim()) {
      newErrors.lastName = 'Le nom est requis';
    }
    
    if (!formData.email) {
      newErrors.email = 'L\'email est requis';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email invalide';
    }
    
    if (!formData.phone.trim()) {
      newErrors.phone = 'Le numéro de téléphone est requis';
    }
    
    if (!formData.password) {
      newErrors.password = 'Le mot de passe est requis';
    } else if (formData.password.length < 8) {
      newErrors.password = 'Le mot de passe doit contenir au moins 8 caractères';
    }
    
    if (formData.password !== formData.confirmPassword) {
      newErrors.confirmPassword = 'Les mots de passe ne correspondent pas';
    }
    
    if (!formData.acceptTerms) {
      newErrors.acceptTerms = 'Vous devez accepter les conditions générales';
    }
    
    return newErrors;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const newErrors = validateForm();
    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    setIsLoading(true);

    try {
      // TODO: API call to register user
      console.log('Register data:', formData);
      
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Redirect to login
      navigate('/login');
    } catch (error) {
      setErrors({ general: 'Une erreur est survenue lors de l\'inscription' });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      <Header bannerData={bannerData} />

      <main>
        <section className="sec-new-account pt-100 pb-100">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">
              Créer un compte
            </h2>

            <div className="blc-compte bg-beige wow fadeInUp" data-wow-delay="400ms">
              <form onSubmit={handleSubmit}>
                {/* Informations personnelles */}
                <div className="informations">
                  <h3><strong>01.</strong> Informations personnelles</h3>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>Prénom <span>*</span></label>
                      <input
                        type="text"
                        name="firstName"
                        value={formData.firstName}
                        onChange={handleChange}
                        className={`form-control ${errors.firstName ? 'error' : ''}`}
                        placeholder="Votre prénom"
                      />
                      {errors.firstName && <span className="txt-error">{errors.firstName}</span>}
                    </div>
                    <div className="blc-chp col50">
                      <label>Nom <span>*</span></label>
                      <input
                        type="text"
                        name="lastName"
                        value={formData.lastName}
                        onChange={handleChange}
                        className={`form-control ${errors.lastName ? 'error' : ''}`}
                        placeholder="Votre nom"
                      />
                      {errors.lastName && <span className="txt-error">{errors.lastName}</span>}
                    </div>
                  </div>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>Email <span>*</span></label>
                      <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        className={`form-control ${errors.email ? 'error' : ''}`}
                        placeholder="exemple@email.com"
                      />
                      {errors.email && <span className="txt-error">{errors.email}</span>}
                    </div>
                    <div className="blc-chp col50">
                      <label>Téléphone <span>*</span></label>
                      <div className="blc-tel">
                        <div className="ico">
                          <img src="/images/mdg.png" alt="Madagascar" />
                        </div>
                        <select 
                          className="select-tel" 
                          name="phoneCountryCode" 
                          value={formData.phoneCountryCode} 
                          onChange={handleChange}
                        >
                          <option value="+261">+261</option>
                          <option value="+262">+262</option>
                          <option value="+33">+33</option>
                        </select>
                        <input
                          type="text"
                          name="phone"
                          value={formData.phone}
                          onChange={handleChange}
                          className={`form-control tel ${errors.phone ? 'error' : ''}`}
                          placeholder="34 12 345 67"
                        />
                      </div>
                      {errors.phone && <span className="txt-error">{errors.phone}</span>}
                    </div>
                  </div>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>Région</label>
                      <select name="region" value={formData.region} onChange={handleChange}>
                        <option value="">Sélectionnez une région</option>
                        <option value="Analamanga">Analamanga</option>
                        <option value="Itasy">Itasy</option>
                        <option value="Vakinankaratra">Vakinankaratra</option>
                        <option value="Bongolava">Bongolava</option>
                        <option value="Haute Matsiatra">Haute Matsiatra</option>
                        <option value="Amoron'i Mania">Amoron'i Mania</option>
                        <option value="Vatovavy Fitovinany">Vatovavy Fitovinany</option>
                        <option value="Atsinanana">Atsinanana</option>
                        <option value="Analanjirofo">Analanjirofo</option>
                        <option value="Alaotra Mangoro">Alaotra Mangoro</option>
                      </select>
                    </div>
                    <div className="blc-chp col50">
                      <label>Ville</label>
                      <input
                        type="text"
                        name="city"
                        value={formData.city}
                        onChange={handleChange}
                        className="form-control"
                        placeholder="Votre ville"
                      />
                    </div>
                  </div>

                  <div className="blc-chp">
                    <label>Code postal</label>
                    <input
                      type="text"
                      name="postalCode"
                      value={formData.postalCode}
                      onChange={handleChange}
                      className="form-control"
                      placeholder="Ex: 101"
                    />
                  </div>
                </div>

                <div className="sep"></div>

                {/* Mot de passe */}
                <div className="informations no-border">
                  <h3><strong>02.</strong> Mot de passe</h3>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>Mot de passe <span>*</span></label>
                      <div style={{ position: 'relative' }}>
                        <input
                          type={showPassword ? 'text' : 'password'}
                          name="password"
                          value={formData.password}
                          onChange={handleChange}
                          className={`form-control ${errors.password ? 'error' : ''}`}
                          placeholder="••••••••"
                        />
                        <button
                          type="button"
                          onClick={() => setShowPassword(!showPassword)}
                          style={{
                            position: 'absolute',
                            right: '10px',
                            top: '50%',
                            transform: 'translateY(-50%)',
                            background: 'none',
                            border: 'none',
                            cursor: 'pointer'
                          }}
                        >
                          {showPassword ? '👁️' : '👁️‍🗨️'}
                        </button>
                      </div>
                      {errors.password && <span className="txt-error">{errors.password}</span>}
                      <small className="form-hint">Au moins 8 caractères</small>
                    </div>
                    <div className="blc-chp col50">
                      <label>Confirmer le mot de passe <span>*</span></label>
                      <div style={{ position: 'relative' }}>
                        <input
                          type={showConfirmPassword ? 'text' : 'password'}
                          name="confirmPassword"
                          value={formData.confirmPassword}
                          onChange={handleChange}
                          className={`form-control ${errors.confirmPassword ? 'error' : ''}`}
                          placeholder="••••••••"
                        />
                        <button
                          type="button"
                          onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                          style={{
                            position: 'absolute',
                            right: '10px',
                            top: '50%',
                            transform: 'translateY(-50%)',
                            background: 'none',
                            border: 'none',
                            cursor: 'pointer'
                          }}
                        >
                          {showConfirmPassword ? '👁️' : '👁️‍🗨️'}
                        </button>
                      </div>
                      {errors.confirmPassword && <span className="txt-error">{errors.confirmPassword}</span>}
                    </div>
                  </div>

                  <div className="blc-check blc-chp">
                    <div className="check-connect">
                      <input
                        type="checkbox"
                        id="accept-terms"
                        name="acceptTerms"
                        checked={formData.acceptTerms}
                        onChange={handleChange}
                      />
                      <label htmlFor="accept-terms">
                        J'accepte les <Link to="/terms">conditions générales d'utilisation</Link> et la{' '}
                        <Link to="/privacy">politique de confidentialité</Link>
                      </label>
                    </div>
                    {errors.acceptTerms && <span className="txt-error">{errors.acceptTerms}</span>}
                  </div>
                </div>

                {errors.general && (
                  <div className="txt-error text-center" style={{ marginBottom: '20px' }}>
                    {errors.general}
                  </div>
                )}

                <div className="blc-btn text-center">
                  <button 
                    type="submit" 
                    className="btn-submit check" 
                    disabled={isLoading}
                  >
                    {isLoading ? 'Inscription en cours...' : 'Créer mon compte'}
                  </button>
                  <Link to="/login" className="link-form">
                    Vous avez déjà un compte ? Connectez-vous
                  </Link>
                </div>

                {/* Lien pour devenir organisateur */}
                <div style={{ 
                  marginTop: '30px', 
                  padding: '20px', 
                  background: '#f0f9ff', 
                  borderRadius: '8px',
                  textAlign: 'center'
                }}>
                  <h4 style={{ marginBottom: '10px' }}>Vous souhaitez organiser des événements ?</h4>
                  <p style={{ marginBottom: '15px', color: '#666' }}>
                    Créez et gérez vos événements, vendez des billets et suivez vos statistiques.
                  </p>
                  <Link to="/become-organizer" className="btn">
                    Devenir organisateur
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

export default Register;
