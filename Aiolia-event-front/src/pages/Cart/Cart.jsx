import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';
import { useCart } from '../../contexts/CartContext';

function Cart() {
  const navigate = useNavigate();
  const { cartItems, removeFromCart, updateQuantity, getCartTotal, clearCart } = useCart();
  const [promoCode, setPromoCode] = useState('');
  const [discount, setDiscount] = useState(0);
  const [promoMessage, setPromoMessage] = useState('');

  const handleApplyPromo = async () => {
    // TODO: Validation API du code promo
    if (promoCode.toLowerCase() === 'promo10') {
      setDiscount(0.1);
      setPromoMessage('Code promo appliqué ! -10%');
    } else {
      setPromoMessage('Code promo invalide');
      setDiscount(0);
    }
  };

  const subtotal = getCartTotal();
  const discountAmount = subtotal * discount;
  const total = subtotal - discountAmount;

  const handleCheckout = () => {
    if (cartItems.length === 0) {
      alert('Votre panier est vide');
      return;
    }
    navigate('/checkout');
  };

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-login pt-100 pb-100">
          <div className="container">
            <h2 className="text-center">Mon Panier</h2>

            {cartItems.length === 0 ? (
              <div className="blc-login bg-beige text-center">
                <p style={{ padding: '40px 0' }}>Votre panier est vide</p>
                <Link to="/events" className="btn">
                  Découvrir les événements
                </Link>
              </div>
            ) : (
              <div className="blc-compte bg-beige">
                <div className="cart-items">
                  {cartItems.map((item, index) => (
                    <div key={index} className="cart-item" style={{ borderBottom: '1px solid #ddd', padding: '20px 0' }}>
                      <div className="row d-flex">
                        <div className="col50">
                          <h3>{item.eventTitle || 'Événement'}</h3>
                          <p><strong>Type:</strong> {item.ticketTypeName}</p>
                          <p><strong>Prix unitaire:</strong> {item.ticketPrice?.toLocaleString()} MGA</p>
                        </div>
                        <div className="col50">
                          <div className="blc-nbr d-flex">
                            <div className="col">
                              <label>Adulte</label>
                              <div className="numbers-row">
                                <div
                                  className="dec button"
                                  onClick={() => updateQuantity(item.id, Math.max(0, item.adults - 1), item.children)}
                                >
                                  <span>-</span>
                                </div>
                                <input
                                  type="text"
                                  value={item.adults || 0}
                                  readOnly
                                  className="qtt"
                                />
                                <div
                                  className="inc button"
                                  onClick={() => updateQuantity(item.id, item.adults + 1, item.children)}
                                >
                                  <span>+</span>
                                </div>
                              </div>
                            </div>
                            <div className="col">
                              <label>Enfant</label>
                              <div className="numbers-row">
                                <div
                                  className="dec button"
                                  onClick={() => updateQuantity(item.id, item.adults, Math.max(0, item.children - 1))}
                                >
                                  <span>-</span>
                                </div>
                                <input
                                  type="text"
                                  value={item.children || 0}
                                  readOnly
                                  className="qtt"
                                />
                                <div
                                  className="inc button"
                                  onClick={() => updateQuantity(item.id, item.adults, item.children + 1)}
                                >
                                  <span>+</span>
                                </div>
                              </div>
                            </div>
                          </div>
                          <p style={{ textAlign: 'right', marginTop: '10px' }}>
                            <strong>Sous-total:</strong>{' '}
                            {((item.adults + item.children) * item.ticketPrice).toLocaleString()} MGA
                          </p>
                          <button
                            onClick={() => removeFromCart(item.id)}
                            className="btn delete"
                            style={{ float: 'right', marginTop: '10px' }}
                          >
                            Supprimer
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="sep" style={{ margin: '30px 0' }}></div>

                {/* Code promo */}
                <div className="blc-chp">
                  <label>Code promo</label>
                  <div className="row d-flex">
                    <div className="col50">
                      <input
                        type="text"
                        value={promoCode}
                        onChange={(e) => setPromoCode(e.target.value)}
                        className="form-control"
                        placeholder="Entrez votre code promo"
                      />
                    </div>
                    <div className="col50">
                      <button onClick={handleApplyPromo} className="btn">
                        Appliquer
                      </button>
                    </div>
                  </div>
                  {promoMessage && (
                    <div className={discount > 0 ? 'txt-success' : 'txt-error'}>
                      {promoMessage}
                    </div>
                  )}
                </div>

                <div className="sep" style={{ margin: '30px 0' }}></div>

                {/* Récapitulatif */}
                <div className="blc-recap">
                  <h3>Récapitulatif</h3>
                  <div className="row d-flex" style={{ justifyContent: 'space-between', marginBottom: '10px' }}>
                    <span>Sous-total:</span>
                    <strong>{subtotal.toLocaleString()} MGA</strong>
                  </div>
                  {discount > 0 && (
                    <div className="row d-flex" style={{ justifyContent: 'space-between', marginBottom: '10px', color: 'green' }}>
                      <span>Réduction ({(discount * 100).toFixed(0)}%):</span>
                      <strong>-{discountAmount.toLocaleString()} MGA</strong>
                    </div>
                  )}
                  <div className="row d-flex" style={{ justifyContent: 'space-between', marginTop: '20px', fontSize: '24px' }}>
                    <span>Total:</span>
                    <strong>{total.toLocaleString()} MGA</strong>
                  </div>
                </div>

                <div className="blc-btn text-center" style={{ marginTop: '30px' }}>
                  <button onClick={handleCheckout} className="btn-submit login">
                    Procéder au paiement
                  </button>
                  <button onClick={clearCart} className="link-form" style={{ marginTop: '10px', background: 'none', border: 'none', cursor: 'pointer' }}>
                    Vider le panier
                  </button>
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

export default Cart;



