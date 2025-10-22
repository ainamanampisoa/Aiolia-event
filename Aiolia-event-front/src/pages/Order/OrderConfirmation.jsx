import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function OrderConfirmation() {
  const { orderId } = useParams();
  const [order, setOrder] = useState(null);

  useEffect(() => {
    // TODO: Fetch order details from API
    // Données simulées
    const mockOrder = {
      id: orderId,
      date: new Date().toISOString(),
      total: 250000,
      status: 'confirmed',
      tickets: [
        {
          id: 1,
          eventTitle: 'Jazz Show',
          eventDate: '2025-07-20T20:00:00',
          ticketType: 'VIP',
          quantity: 2,
          qrCode: 'QR-CODE-12345',
          price: 150000
        },
        {
          id: 2,
          eventTitle: 'Music Festival',
          eventDate: '2025-08-15T19:00:00',
          ticketType: 'Standard',
          quantity: 1,
          qrCode: 'QR-CODE-67890',
          price: 100000
        }
      ],
      paymentMethod: 'MVola',
      transactionId: 'TXN-' + Date.now()
    };

    setOrder(mockOrder);
  }, [orderId]);

  const downloadTicket = (ticketId) => {
    // TODO: Générer et télécharger le PDF du billet
    alert(`Téléchargement du billet ${ticketId} en cours...`);
  };

  const downloadInvoice = () => {
    // TODO: Générer et télécharger la facture PDF
    alert(`Téléchargement de la facture en cours...`);
  };

  if (!order) {
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

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-new-account pt-100 pb-100">
          <div className="container">
            <div className="text-center" style={{ marginBottom: '40px' }}>
              <div style={{ fontSize: '60px', color: '#4CAF50' }}>✓</div>
              <h2>Paiement confirmé !</h2>
              <p>Votre commande a été validée avec succès</p>
            </div>

            <div className="blc-compte bg-beige">
              <div className="informations">
                <h3>Détails de la commande</h3>
                <div className="row d-flex">
                  <div className="col50">
                    <p><strong>Numéro de commande:</strong> {order.id}</p>
                    <p><strong>Date:</strong> {new Date(order.date).toLocaleString('fr-FR')}</p>
                  </div>
                  <div className="col50">
                    <p><strong>Méthode de paiement:</strong> {order.paymentMethod}</p>
                    <p><strong>Transaction ID:</strong> {order.transactionId}</p>
                  </div>
                </div>
              </div>

              <div className="sep"></div>

              <div className="informations no-border">
                <h3>Vos billets</h3>
                
                {order.tickets.map((ticket, index) => (
                  <div key={ticket.id} style={{ padding: '20px 0', borderBottom: index < order.tickets.length - 1 ? '1px solid #ddd' : 'none' }}>
                    <div className="row d-flex">
                      <div className="col50">
                        <h4>{ticket.eventTitle}</h4>
                        <p>
                          {new Date(ticket.eventDate).toLocaleDateString('fr-FR', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                          })}
                        </p>
                        <p><strong>Type:</strong> {ticket.ticketType}</p>
                        <p><strong>Quantité:</strong> {ticket.quantity}</p>
                      </div>
                      <div className="col50" style={{ textAlign: 'right' }}>
                        <div style={{ marginBottom: '15px' }}>
                          <img
                            src={`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${ticket.qrCode}`}
                            alt="QR Code"
                            style={{ maxWidth: '150px' }}
                          />
                        </div>
                        <button
                          onClick={() => downloadTicket(ticket.id)}
                          className="btn"
                          style={{ marginBottom: '10px' }}
                        >
                          Télécharger le billet PDF
                        </button>
                      </div>
                    </div>
                  </div>
                ))}

                <div className="row d-flex" style={{ justifyContent: 'space-between', marginTop: '30px', fontSize: '20px' }}>
                  <strong>Total payé:</strong>
                  <strong style={{ color: '#C5C1A4' }}>{order.total.toLocaleString()} MGA</strong>
                </div>
              </div>

              <div className="blc-btn text-center" style={{ marginTop: '30px' }}>
                <button onClick={downloadInvoice} className="btn-submit">
                  Télécharger la facture
                </button>
                <Link to="/my-tickets" className="link-form">
                  Voir tous mes billets
                </Link>
                <br />
                <Link to="/events" className="link-form">
                  Découvrir d'autres événements
                </Link>
              </div>

              <div style={{ marginTop: '30px', padding: '20px', background: '#f9f9f9', borderRadius: '8px' }}>
                <h4>📧 Email de confirmation envoyé</h4>
                <p>
                  Un email de confirmation avec vos billets a été envoyé à votre adresse email.
                  Vous recevrez également un rappel 24h avant chaque événement.
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </>
  );
}

export default OrderConfirmation;






