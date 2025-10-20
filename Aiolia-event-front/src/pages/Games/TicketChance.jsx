import { useState } from 'react';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function TicketChance() {
  const [isSpinning, setIsSpinning] = useState(false);
  const [result, setResult] = useState(null);
  const [attemptsLeft, setAttemptsLeft] = useState(3);

  const prizes = [
    { type: 'discount', value: 10, label: '10% de réduction', color: '#FFD700' },
    { type: 'points', value: 100, label: '100 points', color: '#87CEEB' },
    { type: 'discount', value: 25, label: '25% de réduction', color: '#FF6B6B' },
    { type: 'ticket', value: 1, label: 'Billet gratuit!', color: '#4ECDC4' },
    { type: 'points', value: 500, label: '500 points', color: '#95E1D3' },
    { type: 'discount', value: 50, label: '50% de réduction', color: '#F38181' },
    { type: 'nothing', value: 0, label: 'Essayez encore', color: '#BDBDBD' },
    { type: 'points', value: 200, label: '200 points', color: '#C7CEEA' },
  ];

  const spin = () => {
    if (attemptsLeft === 0) {
      alert('Vous avez épuisé vos tentatives d\'aujourd\'hui. Revenez demain !');
      return;
    }

    setIsSpinning(true);
    setResult(null);

    // Simulation du tirage
    setTimeout(() => {
      const randomIndex = Math.floor(Math.random() * prizes.length);
      const prize = prizes[randomIndex];
      
      setResult(prize);
      setIsSpinning(false);
      setAttemptsLeft(prev => prev - 1);
    }, 3000);
  };

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-new-account pt-100 pb-100">
          <div className="container">
            <h2 className="text-center">🎰 Ticket Chance</h2>
            <p className="text-center" style={{ marginBottom: '40px' }}>
              Tentez votre chance et gagnez des réductions ou des billets gratuits !
            </p>

            <div className="blc-compte bg-beige">
              {/* Roue de la fortune */}
              <div style={{ textAlign: 'center', padding: '40px 0' }}>
                <div style={{
                  width: '400px',
                  height: '400px',
                  margin: '0 auto',
                  position: 'relative',
                  borderRadius: '50%',
                  border: '10px solid #C5C1A4',
                  overflow: 'hidden',
                  display: 'grid',
                  gridTemplateColumns: 'repeat(2, 1fr)',
                  gridTemplateRows: 'repeat(4, 1fr)',
                  transform: isSpinning ? 'rotate(1440deg)' : 'rotate(0deg)',
                  transition: isSpinning ? 'transform 3s cubic-bezier(0.17, 0.67, 0.12, 0.99)' : 'none'
                }}>
                  {prizes.map((prize, index) => (
                    <div
                      key={index}
                      style={{
                        background: prize.color,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        padding: '10px',
                        fontSize: '14px',
                        fontWeight: 'bold',
                        textAlign: 'center',
                        borderRight: '2px solid white',
                        borderBottom: '2px solid white'
                      }}
                    >
                      {prize.label}
                    </div>
                  ))}
                </div>

                {/* Indicateur */}
                <div style={{
                  width: 0,
                  height: 0,
                  borderLeft: '20px solid transparent',
                  borderRight: '20px solid transparent',
                  borderTop: '40px solid #C5C1A4',
                  margin: '-240px auto 0',
                  position: 'relative',
                  zIndex: 10
                }}></div>
              </div>

              {/* Résultat */}
              {result && (
                <div style={{
                  textAlign: 'center',
                  padding: '30px',
                  background: result.color,
                  borderRadius: '15px',
                  margin: '30px 0',
                  fontSize: '24px',
                  fontWeight: 'bold',
                  color: 'white'
                }}>
                  🎉 Félicitations ! Vous avez gagné : {result.label} 🎉
                  {result.type === 'discount' && (
                    <div style={{ marginTop: '15px', fontSize: '16px' }}>
                      <p>Utilisez le code: <strong>CHANCE{result.value}</strong></p>
                    </div>
                  )}
                </div>
              )}

              {/* Informations */}
              <div className="informations no-border">
                <div className="row d-flex" style={{ justifyContent: 'space-around', marginBottom: '30px' }}>
                  <div style={{ textAlign: 'center' }}>
                    <div style={{ fontSize: '48px', fontWeight: 'bold', color: '#C5C1A4' }}>
                      {attemptsLeft}
                    </div>
                    <div>Tentatives restantes</div>
                  </div>
                  <div style={{ textAlign: 'center' }}>
                    <div style={{ fontSize: '48px', fontWeight: 'bold', color: '#4ECDC4' }}>
                      24h
                    </div>
                    <div>Prochaine chance</div>
                  </div>
                </div>

                <div className="blc-btn text-center">
                  <button
                    onClick={spin}
                    className="btn-submit check"
                    disabled={isSpinning || attemptsLeft === 0}
                    style={{ fontSize: '24px', padding: '20px 60px' }}
                  >
                    {isSpinning ? '🎲 En cours...' : attemptsLeft === 0 ? '⏰ Revenez demain' : '🎰 TENTER MA CHANCE'}
                  </button>
                </div>

                <div style={{ marginTop: '40px', padding: '20px', background: '#f9f9f9', borderRadius: '10px' }}>
                  <h4>📜 Règles du jeu</h4>
                  <ul style={{ padding: '0 20px', textAlign: 'left' }}>
                    <li>3 tentatives gratuites par jour</li>
                    <li>Les points gagnés sont automatiquement ajoutés à votre portefeuille</li>
                    <li>Les codes promo sont valables 30 jours</li>
                    <li>Les billets gratuits sont utilisables sur n'importe quel événement</li>
                    <li>Revenez chaque jour pour de nouvelles chances !</li>
                  </ul>
                </div>

                <div style={{ marginTop: '20px', padding: '20px', background: '#e8f5e9', borderRadius: '10px' }}>
                  <h4>💰 Gagnez plus de tentatives</h4>
                  <p>
                    Parrainez un ami et obtenez +2 tentatives bonus !<br />
                    Achetez un billet et recevez +1 tentative gratuite !
                  </p>
                  <button className="btn btn-secondary" style={{ marginTop: '10px' }}>
                    Inviter un ami
                  </button>
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

export default TicketChance;

