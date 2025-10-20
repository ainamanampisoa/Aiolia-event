import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function MyTickets() {
  const [tickets, setTickets] = useState([]);
  const [filter, setFilter] = useState('upcoming'); // upcoming, past, cancelled
  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    fetchTickets();
  }, [filter]);

  const fetchTickets = async () => {
    // TODO: Fetch from API
    const mockTickets = [
      {
        id: 1,
        eventTitle: 'Jazz Music au Le Grand café de la Gare Soarano',
        eventDate: '2025-07-20T20:00:00',
        eventLocation: 'Analakely au Café de la Gare',
        ticketType: 'VIP',
        quantity: 2,
        price: 150000,
        status: 'upcoming',
        qrCode: 'QR-123456',
        category: 'Soirée live'
      },
      {
        id: 2,
        eventTitle: 'Festival Jazz 2025',
        eventDate: '2025-08-15T19:00:00',
        eventLocation: 'Grand Café de la Gare',
        ticketType: 'Standard',
        quantity: 1,
        price: 75000,
        status: 'upcoming',
        qrCode: 'QR-789012',
        category: 'Concert'
      },
      {
        id: 3,
        eventTitle: 'Nuit de la musique',
        eventDate: '2024-12-20T22:00:00',
        eventLocation: 'Club 67 Ha',
        ticketType: 'VIP',
        quantity: 2,
        price: 200000,
        status: 'past',
        qrCode: 'QR-345678',
        category: 'Soirée DJ'
      }
    ];

    const filtered = mockTickets.filter(ticket => ticket.status === filter);
    setTickets(filtered);
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    
    return {
      day: days[date.getDay()],
      dayNumber: date.getDate(),
      month: months[date.getMonth()].substring(0, 4) + '.',
      year: date.getFullYear(),
      time: date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    };
  };

  const downloadTicket = (ticketId) => {
    alert(`Téléchargement du billet ${ticketId} en PDF...`);
  };

  const shareTicket = (ticketId) => {
    alert(`Partage du billet ${ticketId}...`);
  };

  const filteredTickets = tickets.filter(ticket =>
    ticket.eventTitle.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-my-event pt-100 pb-100">
          <div className="container">
            <h2 className="text-center">Mes billets</h2>

            {/* Barre de recherche */}
            <div className="blc-compte bg-beige form-event">
              <form>
                <div className="blc-chp">
                  <label>Rechercher un événement</label>
                  <div className="blc-search">
                    <input type="button" name="" className="search" />
                    <input
                      type="text"
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="form-control"
                      placeholder="Nom de l'événement..."
                    />
                  </div>
                </div>
              </form>
            </div>

            {/* Filtres */}
            <div style={{ textAlign: 'center', margin: '30px 0' }}>
              <button
                onClick={() => setFilter('upcoming')}
                className={`btn ${filter === 'upcoming' ? '' : 'btn-secondary'}`}
                style={{ margin: '0 10px' }}
              >
                À venir
              </button>
              <button
                onClick={() => setFilter('past')}
                className={`btn ${filter === 'past' ? '' : 'btn-secondary'}`}
                style={{ margin: '0 10px' }}
              >
                Passés
              </button>
              <button
                onClick={() => setFilter('cancelled')}
                className={`btn ${filter === 'cancelled' ? '' : 'btn-secondary'}`}
                style={{ margin: '0 10px' }}
              >
                Annulés
              </button>
            </div>

            {/* Liste des billets */}
            {filteredTickets.length === 0 ? (
              <div className="blc-compte bg-beige text-center">
                <p style={{ padding: '40px 0' }}>
                  Aucun billet {filter === 'upcoming' ? 'à venir' : filter === 'past' ? 'passé' : 'annulé'}
                </p>
                <Link to="/events" className="btn">
                  Découvrir les événements
                </Link>
              </div>
            ) : (
              <div className="list-search">
                <div className="titre">
                  <em>{filteredTickets.length}</em> billet{filteredTickets.length > 1 ? 's' : ''}
                </div>
                <div className="lst-my-event d-flex">
                  {filteredTickets.map((ticket) => {
                    const dateInfo = formatDate(ticket.eventDate);
                    return (
                      <div key={ticket.id} className="col50 bg-beige">
                        <h2>{ticket.eventTitle}</h2>
                        <div className="lst-event">
                          <div className="item">
                            <div className="inner">
                              <div className="blc-col1">
                                <div className="col1">
                                  <div className="date">
                                    <span>{dateInfo.day}</span>
                                    <strong>{dateInfo.dayNumber}</strong>
                                    <span className="text-upper">{dateInfo.month} {dateInfo.year}</span>
                                  </div>
                                  <div className="hour">à {dateInfo.time}</div>
                                  <div className="code-barre">
                                    <img
                                      src={`https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${ticket.qrCode}`}
                                      alt="QR Code"
                                    />
                                  </div>
                                </div>
                                <div className="col2">
                                  <span className="bandeau">{ticket.category}</span>
                                  <div className="adresse">Lieu : {ticket.eventLocation}</div>
                                  <div className="tarif">Type : {ticket.ticketType}</div>
                                  <div className="capacite">Quantité : {ticket.quantity} billet{ticket.quantity > 1 ? 's' : ''}</div>
                                  <div className="capacite">Prix total : {(ticket.price * ticket.quantity).toLocaleString()} MGA</div>
                                  <div className="blcBtn d-flex justify-content-between">
                                    <div className="left">
                                      <Link to={`/events/${ticket.id}`} className="btn plus"></Link>
                                    </div>
                                    <div className="right">
                                      <button
                                        onClick={() => downloadTicket(ticket.id)}
                                        className="btn edit"
                                      >
                                        Télécharger PDF
                                      </button>
                                      <button
                                        onClick={() => shareTicket(ticket.id)}
                                        className="btn"
                                        style={{ marginLeft: '10px' }}
                                      >
                                        Partager
                                      </button>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    );
                  })}
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
                    <li className="next">
                      <a href="#"></a>
                    </li>
                  </ul>
                </div>
              </div>
            )}
          </div>
        </section>
      </main>

      <Footer />
    </>
  );
}

export default MyTickets;



