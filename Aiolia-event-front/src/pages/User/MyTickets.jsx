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
                <div className="lst-my-event d-flex" style={{ flexWrap: 'wrap', gap: '15px', justifyContent: 'space-between' }}>
                  {filteredTickets.map((ticket) => {
                    const dateInfo = formatDate(ticket.eventDate);
                    return (
                      <div key={ticket.id} className="bg-beige" style={{ 
                        width: '48%',
                        minHeight: '350px', 
                        display: 'flex', 
                        flexDirection: 'column',
                        padding: '15px',
                        borderRadius: '8px',
                        boxShadow: '0 3px 6px rgba(0,0,0,0.1)',
                        marginBottom: '15px'
                      }}>
                        {/* En-tête avec titre et catégorie */}
                        <div style={{ marginBottom: '15px' }}>
                          <h2 style={{ 
                            margin: '0 0 8px 0', 
                            fontSize: '18px', 
                            lineHeight: '1.2',
                            color: '#1F2D3D'
                          }}>
                            {ticket.eventTitle}
                          </h2>
                          <span className="bandeau" style={{ 
                            display: 'inline-block',
                            background: '#C5C1A4',
                            color: 'white',
                            padding: '3px 10px',
                            borderRadius: '12px',
                            fontSize: '11px',
                            fontWeight: 'bold'
                          }}>
                            {ticket.category}
                          </span>
                        </div>

                        {/* Contenu principal */}
                        <div style={{ flex: 1, display: 'flex', gap: '15px' }}>
                          {/* Colonne gauche - Date et QR Code */}
                          <div style={{ 
                            display: 'flex', 
                            flexDirection: 'column', 
                            alignItems: 'center',
                            minWidth: '100px'
                          }}>
                            <div className="date" style={{
                              textAlign: 'center',
                              background: '#f8f9fa',
                              padding: '12px',
                              borderRadius: '6px',
                              marginBottom: '12px',
                              border: '2px solid #C5C1A4'
                            }}>
                              <span style={{ display: 'block', fontSize: '10px', color: '#666' }}>{dateInfo.day}</span>
                              <strong style={{ display: 'block', fontSize: '24px', color: '#1F2D3D' }}>{dateInfo.dayNumber}</strong>
                              <span className="text-upper" style={{ display: 'block', fontSize: '10px', color: '#666' }}>
                                {dateInfo.month} {dateInfo.year}
                              </span>
                            </div>
                            <div className="hour" style={{ 
                              fontSize: '12px', 
                              color: '#C5C1A4', 
                              fontWeight: 'bold',
                              marginBottom: '12px'
                            }}>
                              à {dateInfo.time}
                            </div>
                            <div className="code-barre" style={{ textAlign: 'center' }}>
                              <img
                                src={`https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=${ticket.qrCode}`}
                                alt="QR Code"
                                style={{ maxWidth: '60px', height: 'auto' }}
                              />
                            </div>
                          </div>

                          {/* Colonne droite - Détails */}
                          <div style={{ flex: 1 }}>
                            <div style={{ 
                              display: 'flex', 
                              flexDirection: 'column', 
                              gap: '6px',
                              marginBottom: '15px'
                            }}>
                              <div className="adresse" style={{ 
                                display: 'flex', 
                                alignItems: 'center', 
                                gap: '6px',
                                fontSize: '12px',
                                color: '#666'
                              }}>
                                <span>📍</span>
                                <span>Lieu : {ticket.eventLocation}</span>
                              </div>
                              <div className="tarif" style={{ 
                                display: 'flex', 
                                alignItems: 'center', 
                                gap: '6px',
                                fontSize: '12px',
                                color: '#666'
                              }}>
                                <span>🎫</span>
                                <span>Type : {ticket.ticketType}</span>
                              </div>
                              <div className="capacite" style={{ 
                                display: 'flex', 
                                alignItems: 'center', 
                                gap: '6px',
                                fontSize: '12px',
                                color: '#666'
                              }}>
                                <span>👥</span>
                                <span>Quantité : {ticket.quantity} billet{ticket.quantity > 1 ? 's' : ''}</span>
                              </div>
                              <div style={{ 
                                display: 'flex', 
                                alignItems: 'center', 
                                gap: '6px',
                                fontSize: '14px',
                                fontWeight: 'bold',
                                color: '#C5C1A4'
                              }}>
                                <span>💰</span>
                                <span>Prix total : {(ticket.price * ticket.quantity).toLocaleString()} MGA</span>
                              </div>
                            </div>
                          </div>
                        </div>

                        {/* Boutons d'action */}
                        <div style={{ 
                          display: 'flex', 
                          gap: '8px', 
                          marginTop: 'auto',
                          paddingTop: '15px'
                        }}>
                          <Link 
                            to={`/events/${ticket.id}`} 
                            className="btn plus"
                            style={{ 
                              minWidth: '35px',
                              height: '35px',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              fontSize: '16px'
                            }}
                            title="Voir détails"
                          >
                            +
                          </Link>
                          <button
                            onClick={() => downloadTicket(ticket.id)}
                            className="btn edit"
                            style={{ flex: 1, height: '35px', fontSize: '12px' }}
                          >
                            Télécharger PDF
                          </button>
                          <button
                            onClick={() => shareTicket(ticket.id)}
                            className="btn"
                            style={{ flex: 1, height: '35px', fontSize: '12px' }}
                          >
                            Partager
                          </button>
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




