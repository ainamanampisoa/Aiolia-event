import { useState } from 'react';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function Profile() {
  const [formData, setFormData] = useState({
    firstName: 'Jean',
    lastName: 'Dupont',
    email: 'jean.dupont@email.com',
    phone: '034 12 345 67',
    phoneCountryCode: '+261',
    region: 'Analamanga',
    city: 'Antananarivo',
    postalCode: '101',
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
  });
  const [profileImage, setProfileImage] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        setProfileImage(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      // TODO: API call to update profile
      console.log('Updating profile:', formData);
      await new Promise(resolve => setTimeout(resolve, 1000));
      alert('Profil mis à jour avec succès !');
    } catch (error) {
      setErrors({ general: 'Erreur lors de la mise à jour' });
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
            <h2 className="text-center">Mon Profil</h2>

            <div className="blc-compte bg-beige">
              <form onSubmit={handleSubmit}>
                {/* Photo de profil */}
                <div style={{ textAlign: 'center', marginBottom: '30px' }}>
                  <div style={{
                    width: '150px',
                    height: '150px',
                    borderRadius: '50%',
                    margin: '0 auto 20px',
                    overflow: 'hidden',
                    border: '3px solid #C5C1A4'
                  }}>
                    <img
                      src={profileImage || '/images/default-avatar.png'}
                      alt="Profile"
                      style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                    />
                  </div>
                  <label htmlFor="profile-image" className="btn" style={{ cursor: 'pointer' }}>
                    Changer la photo
                  </label>
                  <input
                    type="file"
                    id="profile-image"
                    accept="image/*"
                    onChange={handleImageUpload}
                    style={{ display: 'none' }}
                  />
                </div>

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
                        className="form-control"
                      />
                    </div>
                    <div className="blc-chp col50">
                      <label>Nom <span>*</span></label>
                      <input
                        type="text"
                        name="lastName"
                        value={formData.lastName}
                        onChange={handleChange}
                        className="form-control"
                      />
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
                        className="form-control"
                      />
                    </div>
                    <div className="blc-chp col50">
                      <label>Téléphone <span>*</span></label>
                      <div className="blc-tel">
                        <div className="ico">
                          <img src="/images/mdg.png" alt="Madagascar" />
                        </div>
                        <select className="select-tel" name="phoneCountryCode" value={formData.phoneCountryCode} onChange={handleChange}>
                          <option value="+261">+261</option>
                          <option value="+262">+262</option>
                        </select>
                        <input
                          type="text"
                          name="phone"
                          value={formData.phone}
                          onChange={handleChange}
                          className="form-control tel"
                        />
                      </div>
                    </div>
                  </div>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>Région</label>
                      <select name="region" value={formData.region} onChange={handleChange}>
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
                      />
                    </div>
                  </div>
                </div>

                <div className="sep"></div>

                <div className="informations no-border">
                  <h3><strong>02.</strong> Changer le mot de passe</h3>

                  <div className="blc-chp">
                    <label>Mot de passe actuel</label>
                    <input
                      type="password"
                      name="currentPassword"
                      value={formData.currentPassword}
                      onChange={handleChange}
                      className="form-control"
                    />
                  </div>

                  <div className="row d-flex">
                    <div className="blc-chp col50">
                      <label>Nouveau mot de passe</label>
                      <input
                        type="password"
                        name="newPassword"
                        value={formData.newPassword}
                        onChange={handleChange}
                        className="form-control"
                      />
                    </div>
                    <div className="blc-chp col50">
                      <label>Confirmer le nouveau mot de passe</label>
                      <input
                        type="password"
                        name="confirmPassword"
                        value={formData.confirmPassword}
                        onChange={handleChange}
                        className="form-control"
                      />
                    </div>
                  </div>
                </div>

                {errors.general && (
                  <div className="txt-error text-center">{errors.general}</div>
                )}

                <div className="blc-btn text-center">
                  <button type="submit" className="btn-submit check" disabled={isLoading}>
                    {isLoading ? 'Mise à jour...' : 'Enregistrer les modifications'}
                  </button>
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

export default Profile;



