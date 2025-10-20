import { useState } from 'react';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';
import { Link } from 'react-router-dom';

function History() {
  const [activeTab, setActiveTab] = useState('purchases'); // purchases, searches

  const purchases = [
    {
      id: 'ORD-001',
      date: '2025-01-15T10:30:00',
      eventTitle: 'Jazz Show',
      eventDate: '2025-07-20T20:00:00',
      ticketType: 'VIP',
      quantity: 2,
      total: 300000,
      status: 'confirmed'
    },
    {
      id: 'ORD-002',
      date: '2025-01-10T14:20:00',
      eventTitle: 'Festival Music',
      eventDate: '2025-08-15T19:00:00',
      ticketType: 'Standard',
      quantity: 1,
      total: 75000,
      status: 'confirmed'
    },
    {
      id: 'ORD-003',
      date: '2024-12-20T16:45:00',
      eventTitle: 'Nuit électro',
      eventDate: '2025-01-05T22:00:00',
      ticketType: 'VIP',
      quantity: 2,
      total: 240000,
      status: 'completed'
    },
  ];

  const searches = [
    { id: 1, query: 'concert jazz', date: '2025-01-18T09:15:00', results: 12 },
    { id: 2, query: 'festival', date: '2025-01-17T14:30:00', results: 8 },
    { id: 3, query: 'soirée antananarivo', date: '2025-01-15T11:20:00', results: 15 },
    { id: 4, query: 'événement famille', date: '2025-01-12T16:45:00', results: 20 },
    { id: 5, query: 'concert rock', date: '2025-01-10T13:00:00', results: 5 },
  ];

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-new-account pt-100 pb-100">
          <div className="container">
            <h2 className="text-center">Mon Historique</h2>

            <div style={{ textAlign: 'center', margin: '30px 0' }}>
              <button
                onClick={() => setActiveTab('purchases')}
                className={`btn ${activeTab === 'purchases' ? '' : 'btn-secondary'}`}
                style={{ margin: '0 10px' }}
              >
                Historique d'achats
              </button>
              <button
                onClick={() => setActiveTab('searches')}
                className={`btn ${activeTab === 'searches' ? '' : 'btn-secondary'}`}
                style={{ margin: '0 10px' }}
              >
                Historique de recherche
              </button>
            </div>

            <div className="blc-compte bg-beige">
              {activeTab === 'purchases' ? (
                <div className="informations no-border">
                  <h3>Historique d'achats</h3>
                  {purchases.map((purchase) => (
                    <div key={purchase.id} style={{ padding: '20px 0', borderBottom: '1px solid #ddd' }}>
                      <div className="row d-flex">
                        <div className="col50">
                          <h4>{purchase.eventTitle}</h4>
                          <p>
                            <strong>Commande:</strong> {purchase.id}<br />
                            <strong>Date d'achat:</strong> {new Date(purchase.date).toLocaleString('fr-FR')}<br />
                            <strong>Date événement:</strong> {new Date(purchase.eventDate).toLocaleString('fr-FR', { dateStyle: 'long' })}<br />
                            <strong>Type:</strong> {purchase.ticketType} × {purchase.quantity}
                          </p>
                          <span className={`badge ${purchase.status === 'confirmed' ? 'badge-success' : 'badge-primary'}`}>
                            {purchase.status === 'confirmed' ? '✓ Confirmé' : '✓ Terminé'}
                          </span>
                        </div>
                        <div className="col50" style={{ textAlign: 'right' }}>
                          <div style={{ fontSize: '24px', fontWeight: 'bold', color: '#C5C1A4', marginBottom: '15px' }}>
                            {purchase.total.toLocaleString()} MGA
                          </div>
                          <Link to={`/order-confirmation/${purchase.id}`} className="btn" style={{ marginBottom: '10px', display: 'inline-block' }}>
                            Voir détails
                          </Link>
                          <br />
                          <button className="btn btn-secondary" style={{ display: 'inline-block' }}>
                            Télécharger facture
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}

                  <div className="row d-flex" style={{ justifyContent: 'space-between', marginTop: '30px', padding: '20px', background: '#f9f9f9', borderRadius: '10px' }}>
                    <strong>Total dépensé:</strong>
                    <strong style={{ fontSize: '24px', color: '#C5C1A4' }}>
                      {purchases.reduce((sum, p) => sum + p.total, 0).toLocaleString()} MGA
                    </strong>
                  </div>
                </div>
              ) : (
                <div className="informations no-border">
                  <h3>Historique de recherche</h3>
                  <p style={{ marginBottom: '20px', color: '#666' }}>
                    Vos dernières recherches pour améliorer vos recommandations
                  </p>
                  {searches.map((search) => (
                    <div key={search.id} style={{ padding: '15px', marginBottom: '10px', background: '#f9f9f9', borderRadius: '8px' }}>
                      <div className="row d-flex">
                        <div className="col50">
                          <strong style={{ fontSize: '18px' }}>"{search.query}"</strong>
                          <br />
                          <small style={{ color: '#666' }}>
                            {new Date(search.date).toLocaleString('fr-FR')}
                          </small>
                        </div>
                        <div className="col50" style={{ textAlign: 'right' }}>
                          <span style={{ color: '#C5C1A4' }}>{search.results} résultats</span>
                          <br />
                          <Link to={`/events?q=${encodeURIComponent(search.query)}`} className="link-form">
                            Rechercher à nouveau
                          </Link>
                        </div>
                      </div>
                    </div>
                  ))}

                  <button
                    onClick={() => alert('Historique de recherche effacé')}
                    className="btn btn-secondary"
                    style={{ marginTop: '20px' }}
                  >
                    Effacer l'historique
                  </button>
                </div>
              )}
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </>
  );
}

export default History;

