import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';
import EventCard from '../../components/common/EventCard';

function EventList() {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  
  // États des filtres
  const [filters, setFilters] = useState({
    eventType: '',
    location: '',
    public: '',
    dateFrom: '',
    dateTo: '',
    priceMin: 5000,
    priceMax: 1000000,
    sortBy: 'price_asc', // price_asc, price_desc, newest, popular
  });

  const bannerData = {
    image: '/images/banner.jpg',
    subtitle: 'Événement à ne pas rater',
    title: 'Jazz show avec Koundé au "Le Grand Café de la Gare"',
    showTimer: true
  };

  useEffect(() => {
    fetchEvents();
  }, [filters, currentPage]);

  const fetchEvents = async () => {
    setLoading(true);
    try {
      // TODO: Appel API réel
      // const response = await eventService.getEvents(filters, currentPage);
      
      // Données simulées
      const mockEvents = Array(5).fill(null).map((_, index) => ({
        id: index + 1,
        title: `Music on Sunday ${index + 1}`,
        category: { name: 'Soirée live' },
        location: 'Analakely au Café de la Gare',
        startDate: '2025-07-20T20:00:00',
        minPrice: 50000 + (index * 10000),
        maxPrice: 150000 + (index * 20000),
        imageUrl: index % 2 === 0 ? '/images/img1.png' : '/images/img2.png',
        ticketTypes: [
          { id: 1, name: 'Offre VIP', price: 150000 + (index * 20000) },
          { id: 2, name: 'Offre Standard', price: 50000 + (index * 10000) }
        ]
      }));
      
      setEvents(mockEvents);
    } catch (error) {
      console.error('Erreur lors du chargement des événements:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({ ...prev, [name]: value }));
  };

  const handleFilterSubmit = (e) => {
    e.preventDefault();
    setCurrentPage(1);
    fetchEvents();
  };


  return (
    <>
      <Header showBanner={true} bannerData={bannerData} />

      <main>
        {/* Section Filtres */}
        <section className="sec-filtre">
          <div className="container">
            <form onSubmit={handleFilterSubmit}>
              <div className="cont-filtre d-flex wow fadeInUp" data-wow-delay="300ms">
                <div className="col">
                  <div className="blc-chp">
                    <label>Type d'événement</label>
                    <select
                      name="eventType"
                      value={filters.eventType}
                      onChange={handleFilterChange}
                    >
                      <option value="">Tous les types</option>
                      <option value="concert">Concert</option>
                      <option value="soiree">Soirée live</option>
                      <option value="festival">Festival</option>
                      <option value="conference">Conférence</option>
                      <option value="sport">Événement sportif</option>
                    </select>
                  </div>

                  <div className="blc-chp">
                    <label>Prix (MGA)</label>
                    <div className="blc-prix-inputs" style={{ display: 'flex', gap: '10px' }}>
                      <div style={{ flex: 1 }}>
                        <input
                          type="number"
                          name="priceMin"
                          value={filters.priceMin}
                          onChange={handleFilterChange}
                          placeholder="Min"
                          min="0"
                          style={{ width: '100%', padding: '8px' }}
                        />
                      </div>
                      <div style={{ flex: 1 }}>
                        <input
                          type="number"
                          name="priceMax"
                          value={filters.priceMax}
                          onChange={handleFilterChange}
                          placeholder="Max"
                          min="0"
                          style={{ width: '100%', padding: '8px' }}
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <div className="col">
                  <div className="blc-chp">
                    <label>Localité</label>
                    <select
                      name="location"
                      value={filters.location}
                      onChange={handleFilterChange}
                    >
                      <option value="">Toutes les localités</option>
                      <option value="antananarivo">Antananarivo</option>
                      <option value="toamasina">Toamasina</option>
                      <option value="antsirabe">Antsirabe</option>
                      <option value="mahajanga">Mahajanga</option>
                      <option value="fianarantsoa">Fianarantsoa</option>
                    </select>
                  </div>

                  <div className="blc-chp">
                    <label>Public</label>
                    <select
                      name="public"
                      value={filters.public}
                      onChange={handleFilterChange}
                    >
                      <option value="">Tous publics</option>
                      <option value="family">Famille (tout public)</option>
                      <option value="adult">Adultes uniquement</option>
                      <option value="teen">Adolescents</option>
                    </select>
                  </div>
                </div>

                <div className="col">
                  <div className="blc-chp">
                    <label>Date</label>
                    <div className="blc-date" style={{ display: 'flex', gap: '10px' }}>
                      <div style={{ flex: 1 }}>
                        <input
                          type="date"
                          name="dateFrom"
                          value={filters.dateFrom}
                          onChange={handleFilterChange}
                          placeholder="Du"
                          style={{ width: '100%', padding: '8px' }}
                        />
                      </div>
                      <div style={{ flex: 1 }}>
                        <input
                          type="date"
                          name="dateTo"
                          value={filters.dateTo}
                          onChange={handleFilterChange}
                          placeholder="Au"
                          style={{ width: '100%', padding: '8px' }}
                        />
                      </div>
                    </div>
                  </div>

                  <div className="blc-chp">
                    <label>Afficher par</label>
                    <div className="blc-option d-flex">
                      <div className="option">
                        <div className="check">
                          <input
                            type="radio"
                            id="price_asc"
                            name="sortBy"
                            value="price_asc"
                            checked={filters.sortBy === 'price_asc'}
                            onChange={handleFilterChange}
                          />
                          <label htmlFor="price_asc">Prix croissant</label>
                        </div>
                        <div className="check">
                          <input
                            type="radio"
                            id="price_desc"
                            name="sortBy"
                            value="price_desc"
                            checked={filters.sortBy === 'price_desc'}
                            onChange={handleFilterChange}
                          />
                          <label htmlFor="price_desc">Prix décroissant</label>
                        </div>
                      </div>

                      <div className="sep"></div>

                      <div className="option">
                        <div className="check">
                          <input
                            type="radio"
                            id="newest"
                            name="sortBy"
                            value="newest"
                            checked={filters.sortBy === 'newest'}
                            onChange={handleFilterChange}
                          />
                          <label htmlFor="newest">Nouveautés</label>
                        </div>
                        <div className="check">
                          <input
                            type="radio"
                            id="popular"
                            name="sortBy"
                            value="popular"
                            checked={filters.sortBy === 'popular'}
                            onChange={handleFilterChange}
                          />
                          <label htmlFor="popular">Popularité</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="blcBtn text-center" style={{ 
                marginTop: '10px', 
                paddingTop: '15px',
                borderTop: '1px solid #e0e0e0'
              }}>
                <button type="submit" className="btn submit" style={{ 
                  padding: '12px 30px',
                  fontSize: '16px',
                  fontWeight: 'bold'
                }}>
                  Rechercher
                </button>
              </div>
            </form>
          </div>
        </section>

        {/* Section Liste des événements */}
        <section className="sec-event all-event">
          <div className="container">
            <h2 className="wow fadeIn" data-wow-delay="300ms">
              Tous les événements
            </h2>

            {loading ? (
              <div className="text-center">Chargement des événements...</div>
            ) : events.length === 0 ? (
              <div className="text-center">
                <p>Aucun événement trouvé avec ces critères.</p>
                <button
                  onClick={() => {
                    setFilters({
                      eventType: '',
                      location: '',
                      public: '',
                      dateFrom: '',
                      dateTo: '',
                      priceMin: 5000,
                      priceMax: 1000000,
                      sortBy: 'price_asc'
                    });
                    setCurrentPage(1);
                  }}
                  className="btn"
                >
                  Réinitialiser les filtres
                </button>
              </div>
            ) : (
              <>
                <div className="lst-event">
                  {events.map(event => (
                    <EventCard key={event.id} event={event} showTicketSelector={true} />
                  ))}
                </div>

                {/* Pagination */}
                <div className="pagination">
                  <ul>
                    <li className={currentPage === 1 ? 'prev disabled' : 'prev'}>
                      <a
                        href="#"
                        onClick={(e) => {
                          e.preventDefault();
                          if (currentPage > 1) setCurrentPage(currentPage - 1);
                        }}
                      ></a>
                    </li>
                    {[1, 2, 3, 4].map(page => (
                      <li key={page} className={currentPage === page ? 'selected' : ''}>
                        <a
                          href="#"
                          onClick={(e) => {
                            e.preventDefault();
                            setCurrentPage(page);
                          }}
                        >
                          {page}
                        </a>
                      </li>
                    ))}
                    <li className="next">
                      <a
                        href="#"
                        onClick={(e) => {
                          e.preventDefault();
                          setCurrentPage(currentPage + 1);
                        }}
                      ></a>
                    </li>
                  </ul>
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

export default EventList;





