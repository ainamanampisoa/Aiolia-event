import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function Register() {
  const navigate = useNavigate();
  const [accountType, setAccountType] = useState('organizer');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [formData, setFormData] = useState({
    // Informations personnelles
    fullName: '',
    email: '',
    phoneCountryCode: '+261',
    phone: '',
    region: '',
    city: '',
    postalCode: '',
    password: '',
    confirmPassword: '',
    
    // Informations organisation (si organisateur)
    organizationName: '',
    activities: '',
    nif: '',
    stat: '',
    minTourNumber: '',
    website: '',
    
    differentManager: false,
    managerName: '',
    managerEmail: '',
    managerPhoneCountryCode: '+261',
    managerPhone: '',
    
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
    
    // Validation informations personnelles
    if (!formData.fullName.trim()) {
      newErrors.fullName = 'Le nom complet est requis';
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
    } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/.test(formData.password)) {
      newErrors.password = 'Le mot de passe doit contenir au moins une majuscule, un symbole et un chiffre';
    }
    
    if (formData.password !== formData.confirmPassword) {
      newErrors.confirmPassword = 'Les mots de passe ne correspondent pas';
    }
    
    // Validation informations organisation (si organisateur)
    if (accountType === 'organizer') {
      if (!formData.organizationName.trim()) {
        newErrors.organizationName = 'Le nom de l\'organisation est requis';
      }
      if (!formData.nif.trim()) {
        newErrors.nif = 'Le NIF est requis';
      }
      if (!formData.stat.trim()) {
        newErrors.stat = 'Le STAT est requis';
      }
      if (!formData.minTourNumber.trim()) {
        newErrors.minTourNumber = 'Le numéro MIN TOUR est requis';
      }
      
      if (formData.differentManager) {
        if (!formData.managerName.trim()) {
          newErrors.managerName = 'Le nom du gérant est requis';
        }
        if (!formData.managerEmail.trim()) {
          newErrors.managerEmail = 'L\'email du gérant est requis';
        }
        if (!formData.managerPhone.trim()) {
          newErrors.managerPhone = 'Le téléphone du gérant est requis';
        }
      }
    }
    
    if (!formData.acceptTerms) {
      newErrors.acceptTerms = 'Vous devez accepter les conditions';
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
      const dataToSend = { ...formData, accountType };
      console.log('Register data:', dataToSend);
      
      // Simulation d'un appel API
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      alert('Inscription réussie ! (à connecter avec API)');
      navigate('/login');
    } catch (error) {
      setErrors({ general: 'Erreur lors de l\'inscription. Veuillez réessayer.' });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      <Header showBanner={true} bannerData={bannerData} />
      
      <main>
        <section className="sec-new-account pt-100 pb-100">
          <div className="container">
            <h2 className="text-center">Créer un nouveau compte</h2>
            <div className="blc-compte bg-beige">
              <form onSubmit={handleSubmit}>
                <div className="blc-option d-flex">
                  <label>Je souhaite créer un compte en tant que :</label>
                  <div className="option d-flex">
                    <div className="check">
                      <input
                        type="radio"
                        id="organisateur"
                        name="radio-group"
                        checked={accountType === 'organizer'}
                        onChange={() => setAccountType('organizer')}
                      />
                      <label htmlFor="organisateur">Organisateur</label>
                    </div>
                    <div className="check">
                      <input
                        type="radio"
                        id="particulier"
                        name="radio-group"
                        checked={accountType === 'user'}
                        onChange={() => setAccountType('user')}
                      />
                      <label htmlFor="particulier">Particulier</label>
                    </div>
                  </div>
                </div>

                {/* Informations personnelles */}
                <div className="informations">
                  <h3>
                    <strong>01.</strong> Informations personnelles
                  </h3>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>
                        Votre nom complet <span>*</span>
                      </label>
                      <input
                        type="text"
                        name="fullName"
                        value={formData.fullName}
                        onChange={handleChange}
                        className={`form-control ${errors.fullName ? 'error' : ''}`}
                      />
                      {errors.fullName && <div className="txt-error">{errors.fullName}</div>}
                    </div>
                    <div className="blc-chp col50">
                      <label>
                        Votre adresse email <span>*</span>
                      </label>
                      <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        className={`form-control ${errors.email ? 'error' : ''}`}
                      />
                      {errors.email && <div className="txt-error">{errors.email}</div>}
                    </div>
                  </div>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>
                        Votre numéro de téléphone <span>*</span>
                      </label>
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
                        </select>
                        <input
                          type="text"
                          name="phone"
                          value={formData.phone}
                          onChange={handleChange}
                          className={`form-control tel ${errors.phone ? 'error' : ''}`}
                        />
                      </div>
                      {errors.phone && <div className="txt-error">{errors.phone}</div>}
                    </div>
                    <div className="blc-chp col50">
                      <label>Région</label>
                      <select
                        name="region"
                        value={formData.region}
                        onChange={handleChange}
                      >
                        <option value="">Sélectionner</option>
                        <option value="Analamanga">Analamanga</option>
                        <option value="Itasy">Itasy</option>
                      </select>
                    </div>
                  </div>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>Ville</label>
                      <input
                        type="text"
                        name="city"
                        value={formData.city}
                        onChange={handleChange}
                        className="form-control"
                      />
                    </div>
                    <div className="blc-chp col50">
                      <label>Code postal</label>
                      <input
                        type="text"
                        name="postalCode"
                        value={formData.postalCode}
                        onChange={handleChange}
                        className="form-control code-postale"
                      />
                    </div>
                  </div>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>
                        Votre mot de passe <span>*</span>
                      </label>
                      <div className="blc-password">
                        <input
                          type={showPassword ? 'text' : 'password'}
                          name="password"
                          value={formData.password}
                          onChange={handleChange}
                          className={`form-control ${errors.password ? 'error' : ''}`}
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
                    <div className="blc-chp col50">
                      <label>
                        Confirmer votre mot de passe <span>*</span>
                      </label>
                      <div className="blc-password">
                        <input
                          type={showConfirmPassword ? 'text' : 'password'}
                          name="confirmPassword"
                          value={formData.confirmPassword}
                          onChange={handleChange}
                          className={`form-control ${errors.confirmPassword ? 'error' : ''}`}
                        />
                        <span
                          className="material-symbols-rounded pointer toggle-password"
                          onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                        >
                          {showConfirmPassword ? 'visibility_off' : 'visibility'}
                        </span>
                      </div>
                      {errors.confirmPassword && <div className="txt-error">{errors.confirmPassword}</div>}
                    </div>
                  </div>
                </div>

                {/* Informations organisation (si organisateur) */}
                {accountType === 'organizer' && (
                  <>
                    <div className="sep"></div>
                    <div className="informations no-border">
                      <h3>
                        <strong>02.</strong> Informations sur votre organisation
                      </h3>
                      
                      <div className="row d-flex">
                        <div className="blc-chp col50">
                          <label>
                            Le nom de votre organisation ou société <span>*</span>
                          </label>
                          <input
                            type="text"
                            name="organizationName"
                            value={formData.organizationName}
                            onChange={handleChange}
                            className={`form-control ${errors.organizationName ? 'error' : ''}`}
                          />
                          {errors.organizationName && <div className="txt-error">{errors.organizationName}</div>}
                        </div>
                        <div className="blc-chp col50">
                          <label>Activités</label>
                          <input
                            type="text"
                            name="activities"
                            value={formData.activities}
                            onChange={handleChange}
                            className="form-control"
                          />
                        </div>
                      </div>

                      <div className="row d-flex">
                        <div className="blc-chp col50">
                          <label>
                            NIF <span>*</span>
                          </label>
                          <input
                            type="text"
                            name="nif"
                            value={formData.nif}
                            onChange={handleChange}
                            className={`form-control ${errors.nif ? 'error' : ''}`}
                          />
                          {errors.nif && <div className="txt-error">{errors.nif}</div>}
                        </div>
                        <div className="blc-chp col50">
                          <label>
                            STAT <span>*</span>
                          </label>
                          <input
                            type="text"
                            name="stat"
                            value={formData.stat}
                            onChange={handleChange}
                            className={`form-control ${errors.stat ? 'error' : ''}`}
                          />
                          {errors.stat && <div className="txt-error">{errors.stat}</div>}
                        </div>
                      </div>

                      <div className="row d-flex">
                        <div className="blc-chp col50">
                          <label>
                            Numéro ouverture MIN TOUR <span>*</span>
                            <div className="info">
                              <img src="/images/info.png" alt="info" />
                              <div className="tooltip">
                                Numéro d'ouverture auprès du Ministère du Tourisme
                              </div>
                            </div>
                          </label>
                          <input
                            type="text"
                            name="minTourNumber"
                            value={formData.minTourNumber}
                            onChange={handleChange}
                            className={`form-control ${errors.minTourNumber ? 'error' : ''}`}
                          />
                          {errors.minTourNumber && <div className="txt-error">{errors.minTourNumber}</div>}
                        </div>
                        <div className="blc-chp col50">
                          <label>Site web</label>
                          <input
                            type="text"
                            name="website"
                            value={formData.website}
                            onChange={handleChange}
                            className="form-control"
                          />
                        </div>
                      </div>

                      <div className="blc-check blc-chp">
                        <div className="check-connect">
                          <input
                            type="checkbox"
                            id="info1"
                            name="differentManager"
                            checked={formData.differentManager}
                            onChange={handleChange}
                          />
                          <label htmlFor="info1">
                            Informations sur le gérant ou président de l'association différentes
                          </label>
                        </div>
                      </div>

                      {formData.differentManager && (
                        <>
                          <div className="row d-flex">
                            <div className="blc-chp col50">
                              <label>
                                Nom du gérant/président <span>*</span>
                              </label>
                              <input
                                type="text"
                                name="managerName"
                                value={formData.managerName}
                                onChange={handleChange}
                                className={`form-control ${errors.managerName ? 'error' : ''}`}
                              />
                              {errors.managerName && <div className="txt-error">{errors.managerName}</div>}
                            </div>
                            <div className="blc-chp col50">
                              <label>
                                Adresse email du gérant/président <span>*</span>
                              </label>
                              <input
                                type="email"
                                name="managerEmail"
                                value={formData.managerEmail}
                                onChange={handleChange}
                                className={`form-control ${errors.managerEmail ? 'error' : ''}`}
                              />
                              {errors.managerEmail && <div className="txt-error">{errors.managerEmail}</div>}
                            </div>
                          </div>

                          <div className="row d-flex">
                            <div className="blc-chp col50">
                              <label>
                                Numéro de téléphone <span>*</span>
                              </label>
                              <div className="blc-tel">
                                <div className="ico">
                                  <img src="/images/mdg.png" alt="Madagascar" />
                                </div>
                                <select
                                  className="select-tel"
                                  name="managerPhoneCountryCode"
                                  value={formData.managerPhoneCountryCode}
                                  onChange={handleChange}
                                >
                                  <option value="+261">+261</option>
                                  <option value="+262">+262</option>
                                </select>
                                <input
                                  type="text"
                                  name="managerPhone"
                                  value={formData.managerPhone}
                                  onChange={handleChange}
                                  className={`form-control tel ${errors.managerPhone ? 'error' : ''}`}
                                />
                              </div>
                              {errors.managerPhone && <div className="txt-error">{errors.managerPhone}</div>}
                            </div>
                          </div>
                        </>
                      )}

                      <div className="blc-check blc-chp">
                        <div className="check-connect">
                          <input
                            type="checkbox"
                            id="info2"
                            name="acceptTerms"
                            checked={formData.acceptTerms}
                            onChange={handleChange}
                          />
                          <label htmlFor="info2">
                            J'accepte les{' '}
                            <Link to="/terms">conditions générales d'utilisation</Link> et la{' '}
                            <Link to="/privacy">politique de confidentialité.</Link>
                          </label>
                        </div>
                        {errors.acceptTerms && <div className="txt-error">{errors.acceptTerms}</div>}
                      </div>
                    </div>
                  </>
                )}

                {accountType === 'user' && (
                  <div className="blc-check blc-chp">
                    <div className="check-connect">
                      <input
                        type="checkbox"
                        id="accept-terms-user"
                        name="acceptTerms"
                        checked={formData.acceptTerms}
                        onChange={handleChange}
                      />
                      <label htmlFor="accept-terms-user">
                        J'accepte les{' '}
                        <Link to="/terms">conditions générales d'utilisation</Link> et la{' '}
                        <Link to="/privacy">politique de confidentialité.</Link>
                      </label>
                    </div>
                    {errors.acceptTerms && <div className="txt-error">{errors.acceptTerms}</div>}
                  </div>
                )}

                {errors.general && (
                  <div className="txt-error text-center">{errors.general}</div>
                )}

                <div className="blc-btn text-center">
                  <input
                    type="submit"
                    className="btn-submit check"
                    name=""
                    value={isLoading ? 'Création en cours...' : 'créer mon compte'}
                    disabled={isLoading}
                  />
                  <Link to="/" className="link-form">
                    annuler / retour à la page d'accueil
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
