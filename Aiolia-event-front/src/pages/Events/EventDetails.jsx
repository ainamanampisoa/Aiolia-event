import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';
import EventCard from '../../components/common/EventCard';
import TicketSelector from '../../components/common/TicketSelector';

function EventDetails() {
  const { id } = useParams();
  const [event, setEvent] = useState(null);
  const [similarEvents, setSimilarEvents] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchEventDetails();
    fetchSimilarEvents();
  }, [id]);

  const fetchEventDetails = async () => {
    setLoading(true);
    try {
      // TODO: Appel API réel
      // const response = await eventService.getEventById(id);
      
      // Données simulées
      const mockEvent = {
        id: id,
        title: 'Jazz Style Music on Sunday evening event',
        description: 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.',
        category: { name: 'Soirée live' },
        location: 'Analakely au Café de la Gare',
        locationDetails: 'Grand Café de la Gare, PK 0 Soarano',
        mapUrl: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d7549.281261584579!2d47.52111267994531!3d-18.90301741478975!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x21f07e02f859da69%3A0x71d77dd98fe324ac!2sGrand%20Caf%C3%A9%20de%20la%20Gare!5e0!3m2!1sfr!2smg!4v1754476892148!5m2!1sfr!2smg',
        startDate: '2025-07-20T20:00:00',
        endDate: '2025-07-21T02:00:00',
        minPrice: 50000,
        maxPrice: 150000,
        capacity: 320,
        imageUrl: '/images/img1.png',
        videoUrl: '/video/video.mp4',
        videoPoster: '/images/poster.jpg',
        hashtags: ['#jazz', '#cafédelagare', '#live', '#soirée'],
        ticketTypes: [
          { id: 1, name: 'Offre VIP', price: 150000, available: 50 },
          { id: 2, name: 'Offre Standard', price: 50000, available: 200 }
        ],
        organizer: {
          name: 'Le Grand Café de la Gare',
          email: 'contact@cafedelagare.mg',
          phone: '+261 34 12 345 67'
        }
      };
      
      setEvent(mockEvent);
    } catch (error) {
      console.error('Erreur lors du chargement de l\'événement:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchSimilarEvents = async () => {
    try {
      // TODO: Appel API réel
      // const response = await eventService.getSimilarEvents(id);
      
      // Données simulées
      const mockSimilarEvents = [
        {
          id: 2,
          title: 'Music on Sunday',
          category: { name: 'Soirée live' },
          location: 'Analakely au Café de la Gare',
          startDate: '2025-08-15T20:00:00',
          minPrice: 50000,
          maxPrice: 150000,
          imageUrl: '/images/img1.png',
          ticketTypes: [{ id: 1, name: 'VIP', price: 150000 }]
        },
        {
          id: 3,
          title: 'Jazz Festival',
          category: { name: 'Soirée live' },
          location: 'Analakely au Café de la Gare',
          startDate: '2025-08-20T20:00:00',
          minPrice: 75000,
          maxPrice: 200000,
          imageUrl: '/images/img2.png',
          ticketTypes: [{ id: 1, name: 'VIP', price: 200000 }]
        }
      ];
      
      setSimilarEvents(mockSimilarEvents);
    } catch (error) {
      console.error('Erreur lors du chargement des événements similaires:', error);
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    const months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    
    return {
      day: days[date.getDay()],
      dayNumber: date.getDate(),
      month: months[date.getMonth()],
      year: date.getFullYear(),
      time: date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    };
  };

  if (loading) {
    return (
      <>
        <Header />
        <main>
          <div className="container text-center" style={{ padding: '100px 0' }}>
            Chargement...
          </div>
        </main>
        <Footer />
      </>
    );
  }

  if (!event) {
    return (
      <>
        <Header />
        <main>
          <div className="container text-center" style={{ padding: '100px 0' }}>
            <h2>Événement non trouvé</h2>
            <Link to="/events" className="btn">Retour aux événements</Link>
          </div>
        </main>
        <Footer />
      </>
    );
  }

  const dateInfo = formatDate(event.startDate);

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-event-details">
          <div className="container">
            <div className="inner-details">
              <h2 className="wow fadeIn" data-wow-delay="300ms">
                {event.title}
              </h2>

              <div className="lst-event wow fadeInUp" data-wow-delay="300ms">
                <div className="item">
                  <div className="inner">
                    <div className="blc-col2">
                      <div className="img">
                        <img src={event.imageUrl} alt={event.title} />
                      </div>
                    </div>

                    <div className="blc-col1">
                      <div className="col1">
                        <div className="date">
                          <span>{dateInfo.day}</span>
                          <strong>{dateInfo.dayNumber}</strong>
                          <span className="text-upper">{dateInfo.month} {dateInfo.year}</span>
                        </div>
                        <div className="hour">à {dateInfo.time}</div>
                        <div className="code-barre">
                          <img src="/images/code1.png" alt="code" />
                        </div>
                      </div>

                      <div className="col2">
                        <span className="bandeau">{event.category.name}</span>
                        <div className="adresse">Lieu : {event.location}</div>
                        <div className="tarif">
                          Tarifs : {event.minPrice.toLocaleString()} MGA - {event.maxPrice.toLocaleString()} MGA
                        </div>
                        <div className="capacite">Capacité : {event.capacity} personnes max.</div>

                        <div id="timer">
                          <div id="days">
                            <div></div>
                          </div>
                          <div id="hours"></div>
                          <div id="minutes"></div>
                          <div id="seconds"></div>
                        </div>
                      </div>
                    </div>

                    <div className="blc-col3">
                      <TicketSelector
                        eventId={event.id}
                        ticketTypes={event.ticketTypes}
                      />
                    </div>
                  </div>

                  {/* Bloc Paiement */}
                  <div className="blc-paiement">
                    <div className="btn-left">
                      <Link to={`/checkout/${event.id}`} className="btn">
                        je souhaite Acheter/réserver un ticket à cet événement
                      </Link>
                    </div>
                    <div className="blc-right">
                      <span>Modes de paiement :</span>
                      <ul>
                        <li>
                          <a href="#">
                            <img src="/images/mastercard.png" alt="Mastercard" />
                          </a>
                        </li>
                        <li>
                          <a href="#">
                            <img src="/images/visa.png" alt="Visa" />
                          </a>
                        </li>
                        <li>
                          <a href="#">
                            <img src="/images/m-vola.png" alt="M-vola" />
                          </a>
                        </li>
                        <li>
                          <a href="#">
                            <img src="/images/orange-money.png" alt="Orange Money" />
                          </a>
                        </li>
                        <li>
                          <a href="#">
                            <img src="/images/airtel.png" alt="Airtel Money" />
                          </a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              {/* Description */}
              <div className="blc-text wow fadeInUp" data-wow-delay="300ms">
                <h3>Description de l'événement</h3>
                <div className="text">
                  <p>{event.description}</p>
                </div>
                <div className="lst-hack">
                  <ul className="d-flex">
                    {event.hashtags.map((tag, index) => (
                      <li key={index}>{tag}</li>
                    ))}
                  </ul>
                </div>
              </div>

              {/* Vidéo */}
              {event.videoUrl && (
                <div className="blc-video wow fadeInUp" data-wow-delay="300ms">
                  <h3>Vidéo de présentation de l'événement</h3>
                  <div className="video-container">
                    <video
                      id="video1"
                      width="1506"
                      height="750"
                      poster={event.videoPoster}
                    >
                      <source src={event.videoUrl} type="video/mp4" />
                    </video>
                    <button className="play-btn" data-video="video1"></button>
                  </div>
                </div>
              )}

              {/* Carte */}
              {event.mapUrl && (
                <div className="blc-map wow fadeInUp" data-wow-delay="300ms">
                  <h3>Lieu de l'événement</h3>
                  <div className="map">
                    <iframe
                      src={event.mapUrl}
                      width="600"
                      height="450"
                      style={{ border: 0 }}
                      allowFullScreen=""
                      loading="lazy"
                      referrerPolicy="no-referrer-when-downgrade"
                    ></iframe>
                  </div>
                </div>
              )}
            </div>
          </div>
        </section>

        {/* Événements similaires */}
        <section className="sec-event all-event">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">
              Événements similaires
            </h2>
            <div className="lst-event">
              {similarEvents.map(similarEvent => (
                <EventCard key={similarEvent.id} event={similarEvent} />
              ))}
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </>
  );
}

export default EventDetails;



