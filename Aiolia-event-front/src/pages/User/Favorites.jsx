import { useState } from 'react';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';
import EventCard from '../../components/common/EventCard';

function Favorites() {
  const [favorites, setFavorites] = useState([
    {
      id: 1,
      title: 'Jazz Festival 2025',
      category: { name: 'Concert' },
      location: 'Grand Café de la Gare',
      startDate: '2025-08-20T19:00:00',
      minPrice: 75000,
      maxPrice: 200000,
      imageUrl: '/images/img1.png',
      ticketTypes: [{ id: 1, name: 'VIP', price: 200000 }],
      addedAt: '2025-01-15'
    },
    {
      id: 2,
      title: 'Soirée électro',
      category: { name: 'Soirée DJ' },
      location: 'Club 67 Ha',
      startDate: '2025-09-10T22:00:00',
      minPrice: 50000,
      maxPrice: 120000,
      imageUrl: '/images/img2.png',
      ticketTypes: [{ id: 1, name: 'Standard', price: 50000 }],
      addedAt: '2025-01-12'
    },
  ]);

  const removeFromFavorites = (eventId) => {
    if (confirm('Retirer cet événement de vos favoris ?')) {
      setFavorites(favorites.filter(fav => fav.id !== eventId));
    }
  };

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-event all-event">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">
              Mes Favoris / Wishlist
            </h2>

            {favorites.length === 0 ? (
              <div className="blc-login bg-beige text-center">
                <p style={{ padding: '40px 0' }}>
                  Vous n'avez pas encore ajouté d'événements à vos favoris
                </p>
                <a href="/events" className="btn">
                  Découvrir les événements
                </a>
              </div>
            ) : (
              <>
                <div className="lst-event">
                  {favorites.map(event => (
                    <div key={event.id} style={{ position: 'relative' }}>
                      <EventCard event={event} />
                      <button
                        onClick={() => removeFromFavorites(event.id)}
                        style={{
                          position: 'absolute',
                          top: '20px',
                          right: '20px',
                          background: 'rgba(255, 255, 255, 0.9)',
                          border: 'none',
                          borderRadius: '50%',
                          width: '40px',
                          height: '40px',
                          fontSize: '24px',
                          cursor: 'pointer',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          color: '#ff0000'
                        }}
                        title="Retirer des favoris"
                      >
                        ♥
                      </button>
                    </div>
                  ))}
                </div>

                <div style={{ textAlign: 'center', marginTop: '40px', padding: '30px', background: '#f9f9f9', borderRadius: '10px' }}>
                  <h3>💡 Astuce</h3>
                  <p>
                    Recevez des notifications lorsque les billets pour vos événements favoris
                    sont disponibles ou lorsqu'il y a des promotions spéciales !
                  </p>
                  <button className="btn">
                    Activer les notifications
                  </button>
                </div>
              </>
            )}
          </div>
        </section>
      </main>

      <Footer />
    </>
  );
}

export default Favorites;

