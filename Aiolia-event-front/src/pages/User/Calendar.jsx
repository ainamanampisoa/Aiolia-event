import { useState } from 'react';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';
import { Link } from 'react-router-dom';

function Calendar() {
  const [selectedDate, setSelectedDate] = useState(new Date());
  
  const events = [
    { id: 1, title: 'Jazz Show', date: '2025-07-20', time: '20:00', location: 'Café de la Gare' },
    { id: 2, title: 'Festival Music', date: '2025-08-15', time: '19:00', location: 'Grand Café' },
    { id: 3, title: 'Soirée électro', date: '2025-09-10', time: '22:00', location: 'Club 67 Ha' },
  ];

  const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return {
      day: date.getDate(),
      month: date.toLocaleDateString('fr-FR', { month: 'long' }),
      year: date.getFullYear()
    };
  };

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-calendar">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">
              Mon Calendrier d'Événements
            </h2>
            
            <div className="txt wow fadeInUp" data-wow-delay="400ms">
              <p>
                Visualisez tous vos événements réservés dans un calendrier pratique.
                Ne manquez plus aucun événement !
              </p>
            </div>

            <div className="calendar wow fadeInUp" data-wow-delay="400ms">
              <div className="col">
                <div className="blcLeft">
                  <div className="blc-datepicker">
                    <div id="datepicker"></div>
                  </div>
                  
                  <div style={{ marginTop: '30px', padding: '20px', background: '#f9f9f9', borderRadius: '10px' }}>
                    <h4>Légende</h4>
                    <div style={{ marginTop: '15px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', marginBottom: '10px' }}>
                        <div style={{ width: '20px', height: '20px', background: '#4CAF50', borderRadius: '50%', marginRight: '10px' }}></div>
                        <span>Événements à venir</span>
                      </div>
                      <div style={{ display: 'flex', alignItems: 'center', marginBottom: '10px' }}>
                        <div style={{ width: '20px', height: '20px', background: '#2196F3', borderRadius: '50%', marginRight: '10px' }}></div>
                        <span>Événements ce mois-ci</span>
                      </div>
                      <div style={{ display: 'flex', alignItems: 'center' }}>
                        <div style={{ width: '20px', height: '20px', background: '#9E9E9E', borderRadius: '50%', marginRight: '10px' }}></div>
                        <span>Événements passés</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="col">
                <div className="blcRight">
                  <h3>Mes prochains événements</h3>
                  
                  {events.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: '40px 0' }}>
                      <p>Aucun événement à venir</p>
                      <Link to="/events" className="btn">
                        Découvrir les événements
                      </Link>
                    </div>
                  ) : (
                    <div className="lst-pro-event">
                      {events.map((event) => {
                        const dateInfo = formatDate(event.date);
                        return (
                          <div key={event.id} className="item">
                            <div className="inner d-flex justify-content-between align-items-center">
                              <div className="left">
                                <h4>{event.title}</h4>
                                <span className="date">
                                  {dateInfo.day} {dateInfo.month} {dateInfo.year}
                                </span>
                                <br />
                                <small style={{ color: '#666' }}>{event.location}</small>
                              </div>
                              <div className="right">
                                <div className="hour">{event.time}</div>
                                <Link to={`/events/${event.id}`} className="link"></Link>
                              </div>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  )}

                  <div style={{ marginTop: '30px', padding: '20px', background: '#e3f2fd', borderRadius: '10px' }}>
                    <h4>📧 Rappels automatiques</h4>
                    <p>
                      Vous recevrez un rappel par email 24h avant chaque événement
                      pour ne rien manquer !
                    </p>
                    <button className="btn btn-secondary" style={{ marginTop: '10px' }}>
                      Configurer les rappels
                    </button>
                  </div>

                  <div style={{ marginTop: '20px', textAlign: 'center' }}>
                    <button className="btn">
                      Exporter mon calendrier (iCal)
                    </button>
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

export default Calendar;

