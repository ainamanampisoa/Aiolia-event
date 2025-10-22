import { Link } from 'react-router-dom';
import { useState, useEffect } from 'react';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function Landing() {
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [quantities, setQuantities] = useState({
    '1-adult': 1,
    '1-child': 1,
    '2-adult': 1,
    '2-child': 1,
    '3-adult': 1,
    '3-child': 1
  });
  const updateQuantity = (eventId, type, change) => {
    const key = `${eventId}-${type}`;
    const currentQty = quantities[key] || 1;
    const newQty = Math.max(1, currentQty + change);
    setQuantities(prev => ({
      ...prev,
      [key]: newQty
    }));
  };

  const handleAddToCart = (eventId) => {
    const adultQty = quantities[`${eventId}-adult`] || 1;
    const childQty = quantities[`${eventId}-child`] || 1;
    console.log(`Ajout au panier - Événement ${eventId}: ${adultQty} adulte(s), ${childQty} enfant(s)`);
    // Ici vous pouvez ajouter la logique pour ajouter au panier
    alert(`Ajouté au panier: ${adultQty} adulte(s) et ${childQty} enfant(s)`);
  };

  useEffect(() => {
    // Attendre que jQuery UI soit chargé
    const initDatepicker = () => {
      if (window.$ && window.$.fn.datepicker) {
        window.$('#datepicker').datepicker({
          dateFormat: 'dd/mm/yy',
          showOtherMonths: true,
          selectOtherMonths: true,
          changeMonth: true,
          changeYear: true,
          yearRange: '2024:2026'
        });
      } else {
        // Réessayer après 100ms si jQuery n'est pas encore chargé
        setTimeout(initDatepicker, 100);
      }
    };

    // Démarrer l'initialisation
    initDatepicker();

    // Fallback après 2 secondes si jQuery UI ne fonctionne pas
    setTimeout(() => {
      const datepicker = document.getElementById('datepicker');
      const fallback = document.getElementById('fallback-calendar');
      
      if (datepicker && fallback && !datepicker.innerHTML.trim()) {
        datepicker.style.display = 'none';
        fallback.style.display = 'block';
      }
    }, 2000);
  }, []);

  const bannerData = {
    image: '/images/banner.jpg',
    subtitle: 'Événement à ne pas rater',
    title: 'Jazz show avec Koundé au "Le Grand Café de la Gare"',
    showTimer: true,
    logo: '/images/cafe-de-la-gare.jpg',
    address: 'PK O - Gare Soarano, Antananarivo Madagascar',
    description: 'Lorem ipsum dolor sit amet, te vituperata efficiendi assueverit nec, eu primis instructior usu. Per et equidem denique pericula.',
    contact: {
      phone: '037 05 001 01',
      email: 'info@cafedelagare.mg',
      website: 'www.cafedelagare.mg'
    }
  };

  return (
    <>
      <Header showBanner={true} bannerData={bannerData} />

      <main>
        {/* Section Événements */}
        <section className="sec-event">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">Événements</h2>
            <div className="lst-event">
              <div className="item wow fadeInUp" data-wow-delay="300ms">
                <div className="inner">
                  <div className="blc-col1">
                    <div className="col1">
                      <div className="date">
                        <span>Dimanche</span>
                        <strong>20</strong>
                        <span className="text-upper">juil. 2025</span>
                      </div>
                      <div className="hour">à 20:00</div>
                      <div className="code-barre">
                        <img src="/images/code1.png" alt="QR Code" />
                      </div>
                    </div>
                    <div className="col2">
                      <span className="bandeau">Soirée live</span>
                      <h3>Music on Sunday</h3>
                      <div className="adresse">Lieu : Analakely au Café de la Gare</div>
                      <div className="tarif">Tarifs : 50.000MGA - 150.000MGA</div>
                      <div className="blcBtn">
                        <Link to="/login" className="btn ticket">
                          Acheter un ticket
                        </Link>
                        <Link to="/login" className="btn details">
                          détails
                        </Link>
                      </div>
                    </div>
                  </div>
                  <div className="blc-col2">
                    <div className="img">
                      <img src="/images/img1.png" alt="ventes tickets" />
                    </div>
                  </div>
                  <div className="blc-col3">
                    <h3>Option sur le ticket</h3>
                    <div className="type">
                      <form>
                        <div className="blc-select">
                          <label>Type de ticket</label>
                          <select>
                            <option>Offre VIP</option>
                            <option>lorem ipsum</option>
                          </select>
                        </div>
                        <div className="blc-nbr d-flex">
                          <div className="col">
                            <label>Adulte</label>
                            <div className="numbers-row">
                              <div className="dec button" onClick={() => updateQuantity(1, 'adult', -1)}><span>-</span></div>
                              <input type="text" name="qtt" value={quantities['1-adult'] || 1} className="qtt" readOnly />
                              <div className="inc button" onClick={() => updateQuantity(1, 'adult', 1)}><span>+</span></div>
                            </div>
                          </div>
                          <div className="col">
                            <label>Enfant <span className="info">
                              <img src="/images/info.png" alt="info" />
                              <div className="tooltip">Un ticket est requis pour les enfants de plus de 10 ans</div>
                            </span></label>
                            <div className="numbers-row">
                              <div className="dec button" onClick={() => updateQuantity(1, 'child', -1)}><span>-</span></div>
                              <input type="text" name="qtt" value={quantities['1-child'] || 1} className="qtt" readOnly />
                              <div className="inc button" onClick={() => updateQuantity(1, 'child', 1)}><span>+</span></div>
                            </div>
                          </div>
                          <div className="col btn-ok">
                            <button className="btn ok" onClick={() => handleAddToCart(1)}>OK</button>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>

              <div className="item wow fadeInUp" data-wow-delay="300ms">
                <div className="inner">
                  <div className="blc-col1">
                    <div className="col1">
                      <div className="date">
                        <span>Dimanche</span>
                        <strong>20</strong>
                        <span className="text-upper">juil. 2025</span>
                      </div>
                      <div className="hour">à 20:00</div>
                      <div className="code-barre">
                        <img src="/images/code1.png" alt="QR Code" />
                      </div>
                    </div>
                    <div className="col2">
                      <span className="bandeau">Soirée live</span>
                      <h3>Music on Sunday</h3>
                      <div className="adresse">Lieu : Analakely au Café de la Gare</div>
                      <div className="tarif">Tarifs : 50.000MGA - 150.000MGA</div>
                      <div className="blcBtn">
                        <button className="btn ticket" onClick={() => toggleTicketOptions(3)}>
                          Acheter un ticket
                        </button>
                        <Link to="/login" className="btn details">
                          détails
                        </Link>
                      </div>
                    </div>
                  </div>
                  <div className="blc-col2">
                    <div className="img">
                      <img src="/images/img1.png" alt="ventes tickets" />
                    </div>
                  </div>
                  <div className="blc-col3">
                    <h3>Option sur le ticket</h3>
                    <div className="type">
                      <form>
                        <div className="blc-select">
                          <label>Type de ticket</label>
                          <select>
                            <option>Offre VIP</option>
                            <option>lorem ipsum</option>
                          </select>
                        </div>
                        <div className="blc-nbr d-flex">
                          <div className="col">
                            <label>Adulte</label>
                            <div className="numbers-row">
                              <div className="dec button" onClick={() => updateQuantity(2, 'adult', -1)}><span>-</span></div>
                              <input type="text" name="qtt" value={quantities['2-adult'] || 1} className="qtt" readOnly />
                              <div className="inc button" onClick={() => updateQuantity(2, 'adult', 1)}><span>+</span></div>
                            </div>
                          </div>
                          <div className="col">
                            <label>Enfant <span className="info">
                              <img src="/images/info.png" alt="info" />
                              <div className="tooltip">Un ticket est requis pour les enfants de plus de 10 ans</div>
                            </span></label>
                            <div className="numbers-row">
                              <div className="dec button" onClick={() => updateQuantity(2, 'child', -1)}><span>-</span></div>
                              <input type="text" name="qtt" value={quantities['2-child'] || 1} className="qtt" readOnly />
                              <div className="inc button" onClick={() => updateQuantity(2, 'child', 1)}><span>+</span></div>
                            </div>
                          </div>
                          <div className="col btn-ok">
                            <button className="btn ok" onClick={() => handleAddToCart(2)}>OK</button>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>

              <div className="item wow fadeInUp" data-wow-delay="300ms">
                <div className="inner">
                  <div className="blc-col1">
                    <div className="col1">
                      <div className="date">
                        <span>Dimanche</span>
                        <strong>20</strong>
                        <span className="text-upper">juil. 2025</span>
                      </div>
                      <div className="hour">à 20:00</div>
                      <div className="code-barre">
                        <img src="/images/code1.png" alt="QR Code" />
                      </div>
                    </div>
                    <div className="col2">
                      <span className="bandeau">Soirée live</span>
                      <h3>Music on Sunday</h3>
                      <div className="adresse">Lieu : Analakely au Café de la Gare</div>
                      <div className="tarif">Tarifs : 50.000MGA - 150.000MGA</div>
                      <div className="blcBtn">
                        <button className="btn ticket" onClick={() => toggleTicketOptions(3)}>
                          Acheter un ticket
                        </button>
                        <Link to="/login" className="btn details">
                          détails
                        </Link>
                      </div>
                    </div>
                  </div>
                  <div className="blc-col2">
                    <div className="img">
                      <img src="/images/img1.png" alt="ventes tickets" />
                    </div>
                  </div>
                  <div className="blc-col3">
                    <h3>Option sur le ticket</h3>
                    <div className="type">
                      <form>
                        <div className="blc-select">
                          <label>Type de ticket</label>
                          <select>
                            <option>Offre VIP</option>
                            <option>lorem ipsum</option>
                          </select>
                        </div>
                        <div className="blc-nbr d-flex">
                          <div className="col">
                            <label>Adulte</label>
                            <div className="numbers-row">
                              <div className="dec button" onClick={() => updateQuantity(3, 'adult', -1)}><span>-</span></div>
                              <input type="text" name="qtt" value={quantities['3-adult'] || 1} className="qtt" readOnly />
                              <div className="inc button" onClick={() => updateQuantity(3, 'adult', 1)}><span>+</span></div>
                            </div>
                          </div>
                          <div className="col">
                            <label>Enfant <span className="info">
                              <img src="/images/info.png" alt="info" />
                              <div className="tooltip">Un ticket est requis pour les enfants de plus de 10 ans</div>
                            </span></label>
                            <div className="numbers-row">
                              <div className="dec button" onClick={() => updateQuantity(3, 'child', -1)}><span>-</span></div>
                              <input type="text" name="qtt" value={quantities['3-child'] || 1} className="qtt" readOnly />
                              <div className="inc button" onClick={() => updateQuantity(3, 'child', 1)}><span>+</span></div>
                            </div>
                          </div>
                          <div className="col btn-ok">
                            <button className="btn ok" onClick={() => handleAddToCart(3)}>OK</button>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="pagination">
              <ul>
                <li className="prev"><a href="#"></a></li>
                <li className="selected"><a href="#">1</a></li>
                <li><a href="#">2</a></li>
                <li><a href="#">3</a></li>
                <li><a href="#">4</a></li>
                <li className="next"><a href="#"></a></li>
              </ul>
            </div>

            <div className="blcBtn-bot text-center">
              <Link to="/login" className="btn" title="Voir tous les événements">
                voir tous les événements
              </Link>
            </div>
          </div>
        </section>

        {/* Section Comment acheter */}
        <section className="sec-bandeau">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">Comment acheter votre ticket ?</h2>
            <div className="lst-step d-flex justify-content-center wow fadeInUp" data-wow-delay="300ms">
              <div className="item">
                <div className="inner">
                  <div className="ico"><img src="/images/ico1.svg" alt="Étape 1" /></div>
                  <h3><strong>01</strong> Réservez vos billets</h3>
                  <p>Lorem ipsum dolor sit amet, te vituperata efficiendi assueverit nec</p>
                </div>
              </div>
              <div className="item">
                <div className="inner">
                  <div className="ico"><img src="/images/ico2.svg" alt="Étape 2" /></div>
                  <h3><strong>02</strong> Confirmez la réservation</h3>
                  <p>Lorem ipsum dolor sit amet, te vituperata efficiendi assueverit nec</p>
                </div>
              </div>
              <div className="item">
                <div className="inner">
                  <div className="ico"><img src="/images/ico3.svg" alt="Étape 3" /></div>
                  <h3><strong>03</strong> Choisissez le mode de paiement</h3>
                  <p>Lorem ipsum dolor sit amet, te vituperata efficiendi assueverit nec</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Section Calendrier */}
        <section className="sec-calendar">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">Calendrier des événements</h2>
            <div className="txt wow fadeInUp" data-wow-delay="400ms">
              <p>Lorem ipsum dolor sit amet, te vituperata efficiendi assueverit nec, eu primis instructior usu. Per et equidem denique pericula. Id mea esse fuisset, ne est viderer dolorum voluptaria. His ex populo volumus tibique.</p>
            </div>

            <div className="calendar wow fadeInUp" data-wow-delay="400ms">
              <div className="col">
                <div className="blcLeft" style={{
                  display: 'flex',
                  justifyContent: 'center',
                  alignItems: 'center',
                  width: '100%',
                  height: '100%'
                }}>
                  <div className="blc-datepicker" style={{
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    width: '100%',
                    height: '100%'
                  }}>
                    <div id="datepicker" style={{
                      width: '100%',
                      maxWidth: '380px',
                      margin: '0 auto'
                    }}></div>
                    {/* Fallback calendrier React si jQuery UI ne fonctionne pas */}
                    <div style={{ 
                      display: 'none',
                      background: '#fff',
                      border: '1px solid #ddd',
                      borderRadius: '8px',
                      padding: '20px',
                      width: '100%',
                      maxWidth: '380px',
                      margin: '0 auto',
                      boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
                    }} id="fallback-calendar">
                      <div style={{ textAlign: 'center', marginBottom: '15px' }}>
                        <h4 style={{ margin: 0, color: '#333', fontSize: '16px' }}>Calendrier des événements</h4>
                      </div>
                      <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: 'repeat(7, 1fr)', 
                        gap: '2px',
                        textAlign: 'center'
                      }}>
                        {['L', 'M', 'M', 'J', 'V', 'S', 'D'].map(day => (
                          <div key={day} style={{ 
                            padding: '10px', 
                            fontWeight: 'bold', 
                            color: '#666',
                            background: '#f8f9fa',
                            fontSize: '14px'
                          }}>
                            {day}
                          </div>
                        ))}
                        {Array.from({ length: 35 }, (_, i) => {
                          const date = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), i - 6);
                          const isCurrentMonth = date.getMonth() === selectedDate.getMonth();
                          const isToday = date.toDateString() === new Date().toDateString();
                          
                          return (
                            <div
                              key={i}
                              style={{
                                padding: '10px',
                                background: isCurrentMonth ? '#fff' : '#f8f9fa',
                                border: isToday ? '2px solid #007bff' : '1px solid #eee',
                                color: isCurrentMonth ? '#333' : '#ccc',
                                cursor: 'pointer',
                                fontSize: '14px',
                                borderRadius: '4px'
                              }}
                            >
                              {date.getDate()}
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col">
                <div className="blcRight">
                  <h3>Prochains événements</h3>
                  <div className="lst-pro-event">
                    <div className="item">
                      <div className="inner d-flex justify-content-between align-items-center">
                        <div className="left">
                          <h4>Music on Sunday</h4>
                          <span className="date">20 Juillet 2025</span>
                        </div>
                        <div className="right">
                          <div className="hour">15:00</div>
                          <Link to="/login" className="link"></Link>
                        </div>
                      </div>
                    </div>
                    <div className="item">
                      <div className="inner d-flex justify-content-between align-items-center">
                        <div className="left">
                          <h4>Music on Sunday</h4>
                          <span className="date">20 Juillet 2025</span>
                        </div>
                        <div className="right">
                          <div className="hour">15:00</div>
                          <Link to="/login" className="link"></Link>
                        </div>
                      </div>
                    </div>
                    <div className="item">
                      <div className="inner d-flex justify-content-between align-items-center">
                        <div className="left">
                          <h4>Music on Sunday</h4>
                          <span className="date">20 Juillet 2025</span>
                        </div>
                        <div className="right">
                          <div className="hour">15:00</div>
                          <Link to="/login" className="link"></Link>
                        </div>
                      </div>
                    </div>
                    <div className="item">
                      <div className="inner d-flex justify-content-between align-items-center">
                        <div className="left">
                          <h4>Music on Sunday</h4>
                          <span className="date">20 Juillet 2025</span>
                        </div>
                        <div className="right">
                          <div className="hour">15:00</div>
                          <Link to="/login" className="link"></Link>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Section Organisateur */}
        <section className="sec-bandeau-v2">
          <div className="container text-center wow fadeIn" data-wow-delay="300ms">
            <h2 className="wow fadeIn" data-wow-delay="300ms">Vous êtes un organisateur?</h2>
            <div className="s-titre">Per et equidem denique pericula. Id mea esse fuisset, ne est viderer dolorum voluptaria.</div>
            <p>Lorem ipsum dolor sit amet, te vituperata efficiendi assueverit nec, eu primis instructior usu. Per et equidem denique pericula. Id mea esse fuisset, ne est viderer dolorum voluptaria. His ex populo volumus tibique.</p>
            <h4>Je souhaites créer un événement</h4>
            <div className="blcBtn d-flex justify-content-center">
              <Link to="/become-organizer" className="btn" title="S'inscrire">
                s'inscrire
              </Link>
              <Link to="/login" className="btn btn-secondary" title="Déjà inscrit">
                déjà inscrit
              </Link>
            </div>
          </div>
        </section>

        {/* Section Texte libre */}
        <section className="sec-texte">
          <div className="container d-flex">
            <div className="left wow fadeInLeft" data-wow-delay="300ms">
              <h2>Zone texte libre (ex: A propos)</h2>
              <p>Lorem ipsum dolor sit amet, te vituperata efficiendi assueverit nec, eu primis instructior usu. Per et equidem denique pericula. Id mea esse fuisset, ne est viderer dolorum voluptaria. His ex populo volumus tibique. Lorem ipsum dolor sit amet, te vituperata efficiendi assueverit nec, eu primis instructior usu.</p>
              <Link to="/login" className="btn plus" title="Lire plus">
                lire plus
              </Link>
            </div>
            <div className="right wow fadeInRight" data-wow-delay="300ms">
              <div className="lstAtout d-flex" id="counter">
                <div className="item">
                  <div className="inner">
                    <div className="ico">
                      <img src="/images/ico4.svg" alt="Événements" />
                    </div>
                    <strong className="count percent" data-count="4893">4893</strong>
                    <span>Événements <br />créés</span>
                  </div>
                </div>
                <div className="item">
                  <div className="inner">
                    <div className="ico">
                      <img src="/images/ico5.svg" alt="Tickets" />
                    </div>
                    <strong className="count percent" data-count="85360">85360</strong>
                    <span>Tickets <br />vendus</span>
                  </div>
                </div>
                <div className="item">
                  <div className="inner">
                    <div className="ico">
                      <img src="/images/ico6.svg" alt="Organisateurs" />
                    </div>
                    <strong className="count percent" data-count="2320">2320</strong>
                    <span>Organisateurs <br />inscrits</span>
                  </div>
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

export default Landing;