import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';
import EventCard from '../../components/common/EventCard';

function Home() {
  const [events, setEvents] = useState([]);
  const [upcomingEvents, setUpcomingEvents] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // TODO: Fetch events from API
    // Données simulées pour le moment
    const mockEvents = [
      {
        id: 1,
        title: 'Music on Sunday',
        category: { name: 'Soirée live' },
        location: 'Analakely au Café de la Gare',
        startDate: '2025-07-20T20:00:00',
        minPrice: 50000,
        maxPrice: 150000,
        imageUrl: '/images/img1.png'
      },
      {
        id: 2,
        title: 'Jazz Festival',
        category: { name: 'Concert' },
        location: 'Grand Café de la Gare',
        startDate: '2025-08-15T19:00:00',
        minPrice: 75000,
        maxPrice: 200000,
        imageUrl: '/images/img2.png'
      },
      {
        id: 3,
        title: 'Nuit électro',
        category: { name: 'Soirée DJ' },
        location: 'Club 67 Ha',
        startDate: '2025-09-10T22:00:00',
        minPrice: 40000,
        maxPrice: 120000,
        imageUrl: '/images/img1.png'
      }
    ];

    setEvents(mockEvents.slice(0, 3));
    setUpcomingEvents(mockEvents);
    setLoading(false);
  }, []);

  const bannerData = {
    image: '/images/banner.jpg',
    title: 'Jazz show avec Koundé au "Le Grand Café de la Gare"',
    subtitle: '',
    logoImage: '/images/cafe-de-la-gare.jpg',
    address: 'PK 0 - Gare Soarano, Antananarivo Madagascar',
    description: 'Lorem ipsum dolor sit amet, te vituperata efficiendi assueverit nec, eu primis instructior usu. Per et equidem denique pericula.',
    contact: {
      phone: '037 05 001 01',
      email: 'info@cafedelagare.mg',
      website: 'www.cafedelagare.mg'
    }
  };

  return (
    <>
      <Header showBanner={false} />

      <header>
        <div className="blc-banner">
          <div className="banner">
            <div className="img" style={{ background: `url(${bannerData.image})` }}></div>
            <div className="txt-banner">
              <div className="container">
                <div className="inner">
                  <div className="content">
                    <div className="img-logo wow fadeIn" data-wow-delay="500ms">
                      <img src={bannerData.logoImage} alt="Vente tickets" />
                    </div>
                    <div className="txt wow fadeIn" data-wow-delay="700ms">
                      <h1>{bannerData.title}</h1>
                      <div className="adresse">{bannerData.address}</div>
                      <p>{bannerData.description}</p>
                      <div className="contact">
                        <ul>
                          <li className="tel">
                            <a href={`tel:${bannerData.contact.phone.replace(/\s/g, '')}`}>
                              <span>{bannerData.contact.phone}</span>
                            </a>
                          </li>
                          <li className="mail">
                            <a href={`mailto:${bannerData.contact.email}`}>
                              <span>{bannerData.contact.email}</span>
                            </a>
                          </li>
                          <li className="web">
                            <a href={`https://${bannerData.contact.website}`} target="_blank" rel="noreferrer">
                              <span>{bannerData.contact.website}</span>
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <main>
        {/* Section Événements */}
        <section className="sec-event">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">
              Évènements
            </h2>
            {loading ? (
              <div className="text-center">Chargement...</div>
            ) : (
              <>
                <div className="lst-event">
                  {events.map(event => (
                    <EventCard key={event.id} event={event} />
                  ))}
                </div>

                <div className="pagination">
                  <ul>
                    <li className="prev">
                      <a href="#"></a>
                    </li>
                    <li className="selected">
                      <a href="#">1</a>
                    </li>
                    <li>
                      <a href="#">2</a>
                    </li>
                    <li>
                      <a href="#">3</a>
                    </li>
                    <li>
                      <a href="#">4</a>
                    </li>
                    <li className="next">
                      <a href="#"></a>
                    </li>
                  </ul>
                </div>

                <div className="blcBtn-bot text-center">
                  <Link to="/events" className="btn" title="Voir tous les évènements">
                    voir tous les évènements
                  </Link>
                </div>
              </>
            )}
          </div>
        </section>

        {/* Section Comment acheter */}
        <section className="sec-bandeau">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">
              Comment acheter votre ticket ?
            </h2>
            <div className="lst-step d-flex justify-content-center wow fadeInUp" data-wow-delay="300ms">
              <div className="item">
                <div className="inner">
                  <div className="ico">
                    <img src="/images/ico1.svg" alt="Réserver" />
                  </div>
                  <h3>
                    <strong>01</strong> Réservez vos billets
                  </h3>
                  <p>Choisissez votre événement et sélectionnez le nombre de billets souhaité</p>
                </div>
              </div>
              <div className="item">
                <div className="inner">
                  <div className="ico">
                    <img src="/images/ico2.svg" alt="Confirmer" />
                  </div>
                  <h3>
                    <strong>02</strong> Confirmez la réservation
                  </h3>
                  <p>Vérifiez vos informations et validez votre commande</p>
                </div>
              </div>
              <div className="item">
                <div className="inner">
                  <div className="ico">
                    <img src="/images/ico3.svg" alt="Payer" />
                  </div>
                  <h3>
                    <strong>03</strong> Choisissez le mode de paiement
                  </h3>
                  <p>Payez en toute sécurité via Mobile Money ou carte bancaire</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Section Calendrier */}
        <section className="sec-calendar">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">
              Calendrier des évènements
            </h2>
            <div className="txt wow fadeInUp" data-wow-delay="400ms">
              <p>
                Consultez le calendrier pour ne manquer aucun événement. Planifiez vos sorties en
                avance et réservez vos billets dès maintenant.
              </p>
            </div>

            <div className="calendar wow fadeInUp" data-wow-delay="400ms">
              <div className="col">
                <div className="blcLeft">
                  <div className="blc-datepicker">
                    <div id="datepicker"></div>
                  </div>
                </div>
              </div>
              <div className="col">
                <div className="blcRight">
                  <h3>Prochains évènements</h3>
                  <div className="lst-pro-event">
                    {upcomingEvents.map(event => (
                      <div key={event.id} className="item">
                        <div className="inner d-flex justify-content-between align-items-center">
                          <div className="left">
                            <h4>{event.title}</h4>
                            <span className="date">
                              {new Date(event.startDate).toLocaleDateString('fr-FR', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric'
                              })}
                            </span>
                          </div>
                          <div className="right">
                            <div className="hour">
                              {new Date(event.startDate).toLocaleTimeString('fr-FR', {
                                hour: '2-digit',
                                minute: '2-digit'
                              })}
                            </div>
                            <Link to={`/events/${event.id}`} className="link"></Link>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Section Organisateur */}
        <section className="sec-bandeau-v2">
          <div className="container text-center wow fadeIn" data-wow-delay="300ms">
            <h2 className="wow fadeIn" data-wow-delay="300ms">
              Vous êtes un organisateur ?
            </h2>
            <div className="s-titre">
              Créez et gérez vos événements facilement
            </div>
            <p>
              Rejoignez notre plateforme et profitez d'outils professionnels pour gérer vos
              événements, vendre vos billets et analyser vos ventes en temps réel.
            </p>
            <h4>Je souhaite créer un événement</h4>
            <div className="blcBtn d-flex justify-content-center">
              <Link to="/register" className="btn" title="S'inscrire">
                s'inscrire
              </Link>
              <Link to="/login" className="btn btn-secondary" title="Déjà inscrit">
                déjà inscrit
              </Link>
            </div>
          </div>
        </section>

        {/* Section Texte libre / Statistiques */}
        <section className="sec-texte">
          <div className="container d-flex">
            <div className="left wow fadeInLeft" data-wow-delay="300ms">
              <h2>À propos d'Aiolia Event</h2>
              <p>
                Aiolia Event est la première plateforme malgache de vente de billets en ligne.
                Notre mission est de faciliter l'accès aux événements culturels, sportifs et de
                divertissement à Madagascar. Rejoignez des milliers d'utilisateurs qui nous font
                confiance pour vivre des expériences inoubliables.
              </p>
              <Link to="/about" className="btn plus" title="Lire plus">
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
                    <strong className="count percent" data-count="4893">
                      4893
                    </strong>
                    <span>
                      Évènements
                      <br />
                      créés
                    </span>
                  </div>
                </div>
                <div className="item">
                  <div className="inner">
                    <div className="ico">
                      <img src="/images/ico5.svg" alt="Tickets" />
                    </div>
                    <strong className="count percent" data-count="85360">
                      85360
                    </strong>
                    <span>
                      Tickets
                      <br />
                      vendus
                    </span>
                  </div>
                </div>
                <div className="item">
                  <div className="inner">
                    <div className="ico">
                      <img src="/images/ico6.svg" alt="Organisateurs" />
                    </div>
                    <strong className="count percent" data-count="2320">
                      2320
                    </strong>
                    <span>
                      Organisateurs
                      <br />
                      inscrits
                    </span>
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

export default Home;



