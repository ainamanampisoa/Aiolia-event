import { useState } from 'react';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function Contact() {
  const [formData, setFormData] = useState({
    userType: 'organisateur',
    fullName: '',
    email: '',
    subject: '',
    message: ''
  });

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    console.log('Formulaire soumis:', formData);
    // Ici vous pouvez ajouter la logique d'envoi du formulaire
  };

  const bannerData = {
    image: '/images/banner.jpg',
    subtitle: 'Événement à ne pas rater',
    title: 'Jazz show avec Koundé au "Le Grand Café de la Gare"',
    showTimer: true
  };

  return (
    <>
      <Header showBanner={true} bannerData={bannerData} />
      
      <main>
        <section className="sec-contact">
          <div className="container">
            <div className="blc-contact">
              <div className="col blc-left">
                <h2>Contactez-nous</h2>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,</p>
                <div className="blc-btn-whatspp">
                  <div className="inner">
                    <span>Whatsapp | </span>
                    <a href="tel:+261340000000">+261 34 000 00 00</a>
                  </div>
                </div>
              </div>
              <div className="col blc-right">
                <div className="blc-formulaire">
                  <form onSubmit={handleSubmit}>
                    <div className="blc-option d-flex">
                      <label>Je suis un</label>
                      <div className="option">
                        <div className="check">
                          <input 
                            type="radio" 
                            id="organisateur" 
                            name="userType" 
                            value="organisateur"
                            checked={formData.userType === 'organisateur'}
                            onChange={handleInputChange}
                          />
                          <label htmlFor="organisateur">Organisateur</label>
                        </div>
                        <div className="check">
                          <input 
                            type="radio" 
                            id="particulier" 
                            name="userType" 
                            value="particulier"
                            checked={formData.userType === 'particulier'}
                            onChange={handleInputChange}
                          />
                          <label htmlFor="particulier">Particulier</label>
                        </div>
                      </div>
                    </div>
                    <h3>Formulaire de contact</h3>
                    <div className="blc-chp">
                      <label>Votre nom complet <span>*</span></label>
                      <input 
                        type="text" 
                        name="fullName"
                        value={formData.fullName}
                        onChange={handleInputChange}
                        className="form-control"
                        required
                      />
                    </div>
                    <div className="blc-chp">
                      <label>Votre adresse mail <span>*</span></label>
                      <input 
                        type="email" 
                        name="email"
                        value={formData.email}
                        onChange={handleInputChange}
                        className="form-control"
                        required
                      />
                    </div>
                    <div className="blc-chp">
                      <label>Objet <span>*</span></label>
                      <select 
                        name="subject"
                        value={formData.subject}
                        onChange={handleInputChange}
                        required
                      >
                        <option value="">Sélectionnez un objet</option>
                        <option value="question">Question générale</option>
                        <option value="support">Support technique</option>
                        <option value="partenariat">Partenariat</option>
                        <option value="autre">Autre</option>
                      </select>
                    </div>
                    <div className="blc-chp">
                      <label>Votre message <span>*</span></label>
                      <textarea 
                        name="message"
                        value={formData.message}
                        onChange={handleInputChange}
                        className="form-control"
                        rows="5"
                        required
                      ></textarea>
                    </div>

                    <div className="blc-btn">
                      <input type="submit" value="Envoyer" className="btn-submit" />
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </>
  );
}

export default Contact;

