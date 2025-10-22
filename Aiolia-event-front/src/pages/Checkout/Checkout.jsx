import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';
import { useCart } from '../../contexts/CartContext';

function Checkout() {
  const navigate = useNavigate();
  const { cartItems, getCartTotal, clearCart } = useCart();
  const [paymentMethod, setPaymentMethod] = useState('mvola');
  const [phoneNumber, setPhoneNumber] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);

  const total = getCartTotal();

  const handlePayment = async (e) => {
    e.preventDefault();
    
    if (!phoneNumber) {
      alert('Veuillez entrer votre numéro de téléphone');
      return;
    }

    setIsProcessing(true);

    try {
      // TODO: Intégration API Mobile Money
      // const response = await paymentService.processMobileMoneyPayment({
      //   amount: total,
      //   phoneNumber,
      //   provider: paymentMethod,
      //   items: cartItems
      // });

      // Simulation
      await new Promise(resolve => setTimeout(resolve, 2000));

      // Générer un ID de commande
      const orderId = 'ORD-' + Date.now();

      // Vider le panier
      clearCart();

      // Rediriger vers la confirmation
      navigate(`/order-confirmation/${orderId}`);
    } catch (error) {
      alert('Erreur lors du paiement. Veuillez réessayer.');
      console.error(error);
    } finally {
      setIsProcessing(false);
    }
  };

  if (cartItems.length === 0) {
    return (
      <>
        <Header />
        <main>
          <div className="container text-center" style={{ padding: '100px 0' }}>
            <h2>Votre panier est vide</h2>
            <button onClick={() => navigate('/events')} className="btn">
              Découvrir les événements
            </button>
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
            <h2 className="text-center">Paiement</h2>

            <div className="blc-compte bg-beige">
              <form onSubmit={handlePayment}>
                {/* Récapitulatif commande */}
                <div className="informations">
                  <h3><strong>01.</strong> Récapitulatif de votre commande</h3>
                  
                  {cartItems.map((item, index) => (
                    <div key={index} style={{ padding: '15px 0', borderBottom: '1px solid #ddd' }}>
                      <div className="row d-flex">
                        <div className="col50">
                          <strong>{item.eventTitle}</strong>
                          <p>{item.ticketTypeName}</p>
                        </div>
                        <div className="col50" style={{ textAlign: 'right' }}>
                          <p>{item.adults + item.children} billet(s)</p>
                          <strong>{((item.adults + item.children) * item.ticketPrice).toLocaleString()} MGA</strong>
                        </div>
                      </div>
                    </div>
                  ))}

                  <div className="row d-flex" style={{ justifyContent: 'space-between', marginTop: '20px', fontSize: '20px' }}>
                    <strong>Total à payer:</strong>
                    <strong style={{ color: '#C5C1A4' }}>{total.toLocaleString()} MGA</strong>
                  </div>
                </div>

                <div className="sep"></div>

                {/* Méthode de paiement */}
                <div className="informations no-border">
                  <h3><strong>02.</strong> Choisissez votre mode de paiement</h3>

                  <div className="blc-paiement">
                    <div className="blc-right">
                      <ul style={{ display: 'flex', gap: '20px', flexWrap: 'wrap', marginBottom: '30px' }}>
                        <li style={{ cursor: 'pointer', opacity: paymentMethod === 'mvola' ? 1 : 0.5 }} onClick={() => setPaymentMethod('mvola')}>
                          <img src="/images/m-vola.png" alt="MVola" />
                        </li>
                        <li style={{ cursor: 'pointer', opacity: paymentMethod === 'orange' ? 1 : 0.5 }} onClick={() => setPaymentMethod('orange')}>
                          <img src="/images/orange-money.png" alt="Orange Money" />
                        </li>
                        <li style={{ cursor: 'pointer', opacity: paymentMethod === 'airtel' ? 1 : 0.5 }} onClick={() => setPaymentMethod('airtel')}>
                          <img src="/images/airtel.png" alt="Airtel Money" />
                        </li>
                      </ul>
                    </div>
                  </div>

                  <div className="blc-chp">
                    <label>Numéro de téléphone <span>*</span></label>
                    <div className="blc-tel">
                      <div className="ico">
                        <img src="/images/mdg.png" alt="Madagascar" />
                      </div>
                      <select className="select-tel">
                        <option value="+261">+261</option>
                      </select>
                      <input
                        type="tel"
                        value={phoneNumber}
                        onChange={(e) => setPhoneNumber(e.target.value)}
                        className="form-control tel"
                        placeholder="34 12 345 67"
                        required
                      />
                    </div>
                    <small className="form-hint">
                      Vous recevrez une notification {paymentMethod.toUpperCase()} pour valider le paiement
                    </small>
                  </div>

                  <div className="blc-check blc-chp">
                    <div className="check-connect">
                      <input type="checkbox" id="accept-terms" required />
                      <label htmlFor="accept-terms">
                        J'accepte les conditions générales de vente et la politique de remboursement
                      </label>
                    </div>
                  </div>
                </div>

                <div className="blc-btn text-center">
                  <button
                    type="submit"
                    className="btn-submit check"
                    disabled={isProcessing}
                  >
                    {isProcessing ? 'Paiement en cours...' : `Payer ${total.toLocaleString()} MGA`}
                  </button>
                  <button
                    type="button"
                    onClick={() => navigate('/cart')}
                    className="link-form"
                    style={{ background: 'none', border: 'none', cursor: 'pointer' }}
                  >
                    Retour au panier
                  </button>
                </div>
              </form>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </>
  );
}

export default Checkout;






