import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function BecomeOrganizer() {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    // Informations personnelles
    firstName: '',
    lastName: '',
    email: '',
    phoneCountryCode: '+261',
    phone: '',
    
    // Informations organisation
    organizationName: '',
    activities: '',
    nif: '',
    stat: '',
    minTourNumber: '',
    website: '',
    region: '',
    city: '',
    postalCode: '',
    
    // Responsable différent ?
    differentManager: false,
    managerName: '',
    managerEmail: '',
    managerPhoneCountryCode: '+261',
    managerPhone: '',
    
    acceptTerms: false
  });
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);

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
    if (!formData.firstName.trim()) newErrors.firstName = 'Le prénom est requis';
    if (!formData.lastName.trim()) newErrors.lastName = 'Le nom est requis';
    if (!formData.email || !/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email valide requis';
    }
    if (!formData.phone.trim()) newErrors.phone = 'Le téléphone est requis';
    
    // Validation informations organisation
    if (!formData.organizationName.trim()) {
      newErrors.organizationName = 'Le nom de l\'organisation est requis';
    }
    if (!formData.nif.trim()) newErrors.nif = 'Le NIF est requis';
    if (!formData.stat.trim()) newErrors.stat = 'Le STAT est requis';
    if (!formData.minTourNumber.trim()) {
      newErrors.minTourNumber = 'Le numéro MIN TOUR est requis';
    }
    
    // Validation responsable (si différent)
    if (formData.differentManager) {
      if (!formData.managerName.trim()) {
        newErrors.managerName = 'Le nom du responsable est requis';
      }
      if (!formData.managerEmail || !/\S+@\S+\.\S+/.test(formData.managerEmail)) {
        newErrors.managerEmail = 'Email valide du responsable requis';
      }
      if (!formData.managerPhone.trim()) {
        newErrors.managerPhone = 'Le téléphone du responsable est requis';
      }
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
      // TODO: API call to register organizer
      console.log('Organizer registration data:', formData);
      
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1500));
      
      // Redirect to success page or dashboard
      alert('Votre demande a été envoyée ! Nous vous contacterons sous 48h pour valider votre compte organisateur.');
      navigate('/');
    } catch (error) {
      setErrors({ general: 'Une erreur est survenue lors de l\'inscription' });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-new-account pt-100 pb-100">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">
              Devenir Organisateur
            </h2>
            
            <div className="txt wow fadeInUp text-center" data-wow-delay="350ms" style={{ marginBottom: '30px' }}>
              <p>
                Organisez vos événements, gérez les billets et maximisez vos ventes.<br />
                Remplissez le formulaire ci-dessous pour créer votre compte organisateur.
              </p>
            </div>

            <div className="blc-compte bg-beige wow fadeInUp" data-wow-delay="400ms">
              <form onSubmit={handleSubmit}>
                {/* Informations personnelles */}
                <div className="informations">
                  <h3><strong>01.</strong> Vos informations personnelles</h3>

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
                </div>

                <div className="sep"></div>

                {/* Informations organisation */}
                <div className="informations">
                  <h3><strong>02.</strong> Informations de votre organisation</h3>

                  <div className="blc-chp">
                    <label>Nom de l'organisation <span>*</span></label>
                    <input
                      type="text"
                      name="organizationName"
                      value={formData.organizationName}
                      onChange={handleChange}
                      className={`form-control ${errors.organizationName ? 'error' : ''}`}
                      placeholder="Nom de votre entreprise ou organisation"
                    />
                    {errors.organizationName && <span className="txt-error">{errors.organizationName}</span>}
                  </div>

                  <div className="blc-chp">
                    <label>Activités</label>
                    <textarea
                      name="activities"
                      value={formData.activities}
                      onChange={handleChange}
                      className="form-control"
                      rows="3"
                      placeholder="Décrivez vos activités..."
                    />
                  </div>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>NIF <span>*</span></label>
                      <input
                        type="text"
                        name="nif"
                        value={formData.nif}
                        onChange={handleChange}
                        className={`form-control ${errors.nif ? 'error' : ''}`}
                        placeholder="Numéro NIF"
                      />
                      {errors.nif && <span className="txt-error">{errors.nif}</span>}
                    </div>
                    <div className="blc-chp col50">
                      <label>STAT <span>*</span></label>
                      <input
                        type="text"
                        name="stat"
                        value={formData.stat}
                        onChange={handleChange}
                        className={`form-control ${errors.stat ? 'error' : ''}`}
                        placeholder="Numéro STAT"
                      />
                      {errors.stat && <span className="txt-error">{errors.stat}</span>}
                    </div>
                  </div>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>MIN TOUR <span>*</span></label>
                      <input
                        type="text"
                        name="minTourNumber"
                        value={formData.minTourNumber}
                        onChange={handleChange}
                        className={`form-control ${errors.minTourNumber ? 'error' : ''}`}
                        placeholder="Numéro MIN TOUR"
                      />
                      {errors.minTourNumber && <span className="txt-error">{errors.minTourNumber}</span>}
                    </div>
                    <div className="blc-chp col50">
                      <label>Site web</label>
                      <input
                        type="url"
                        name="website"
                        value={formData.website}
                        onChange={handleChange}
                        className="form-control"
                        placeholder="https://www.exemple.com"
                      />
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
                </div>

                <div className="sep"></div>

                {/* Responsable différent */}
                <div className="informations no-border">
                  <h3><strong>03.</strong> Responsable de l'organisation</h3>

                  <div className="blc-check blc-chp">
                    <div className="check-connect">
                      <input
                        type="checkbox"
                        id="different-manager"
                        name="differentManager"
                        checked={formData.differentManager}
                        onChange={handleChange}
                      />
                      <label htmlFor="different-manager">
                        Le responsable est une personne différente
                      </label>
                    </div>
                  </div>

                  {formData.differentManager && (
                    <>
                      <div className="blc-chp">
                        <label>Nom complet du responsable <span>*</span></label>
                        <input
                          type="text"
                          name="managerName"
                          value={formData.managerName}
                          onChange={handleChange}
                          className={`form-control ${errors.managerName ? 'error' : ''}`}
                          placeholder="Nom du responsable"
                        />
                        {errors.managerName && <span className="txt-error">{errors.managerName}</span>}
                      </div>

                      <div className="row d-flex">
                        <div className="blc-chp col50">
                          <label>Email du responsable <span>*</span></label>
                          <input
                            type="email"
                            name="managerEmail"
                            value={formData.managerEmail}
                            onChange={handleChange}
                            className={`form-control ${errors.managerEmail ? 'error' : ''}`}
                            placeholder="email@exemple.com"
                          />
                          {errors.managerEmail && <span className="txt-error">{errors.managerEmail}</span>}
                        </div>
                        <div className="blc-chp col50">
                          <label>Téléphone du responsable <span>*</span></label>
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
                            </select>
                            <input
                              type="text"
                              name="managerPhone"
                              value={formData.managerPhone}
                              onChange={handleChange}
                              className={`form-control tel ${errors.managerPhone ? 'error' : ''}`}
                              placeholder="34 12 345 67"
                            />
                          </div>
                          {errors.managerPhone && <span className="txt-error">{errors.managerPhone}</span>}
                        </div>
                      </div>
                    </>
                  )}

                  <div className="blc-check blc-chp">
                    <div className="check-connect">
                      <input
                        type="checkbox"
                        id="accept-terms-organizer"
                        name="acceptTerms"
                        checked={formData.acceptTerms}
                        onChange={handleChange}
                      />
                      <label htmlFor="accept-terms-organizer">
                        J'accepte les <Link to="/terms">conditions générales d'utilisation</Link> et les{' '}
                        <Link to="/organizer-terms">conditions spécifiques organisateurs</Link>
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
                    {isLoading ? 'Envoi en cours...' : 'Soumettre ma demande'}
                  </button>
                  <Link to="/register" className="link-form">
                    Vous êtes un particulier ? Inscrivez-vous ici
                  </Link>
                </div>

                {/* Information sur le processus */}
                <div style={{ 
                  marginTop: '30px', 
                  padding: '20px', 
                  background: '#fff3cd', 
                  borderRadius: '8px',
                  borderLeft: '4px solid #ffc107'
                }}>
                  <h4 style={{ marginBottom: '10px', color: '#856404' }}>📋 Processus de validation</h4>
                  <ul style={{ paddingLeft: '20px', color: '#856404' }}>
                    <li>Votre demande sera examinée sous 48h</li>
                    <li>Nous vérifierons les documents fournis (NIF, STAT, MIN TOUR)</li>
                    <li>Vous recevrez un email de confirmation une fois votre compte validé</li>
                    <li>Vous pourrez alors créer et gérer vos événements</li>
                  </ul>
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

export default BecomeOrganizer;


