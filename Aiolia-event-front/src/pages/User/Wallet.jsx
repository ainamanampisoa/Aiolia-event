import { useState } from 'react';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';
import { Link } from 'react-router-dom';

function Wallet() {
  const [balance, setBalance] = useState(2500); // Points de fidélité
  const [transactions] = useState([
    { id: 1, type: 'earn', amount: 500, description: 'Achat de billets - Jazz Show', date: '2025-01-15' },
    { id: 2, type: 'earn', amount: 300, description: 'Parrainage d\'un ami', date: '2025-01-10' },
    { id: 3, type: 'spend', amount: -200, description: 'Réduction sur Music Festival', date: '2025-01-05' },
    { id: 4, type: 'earn', amount: 1000, description: 'Bonus inscription', date: '2025-01-01' },
  ]);

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-new-account pt-100 pb-100">
          <div className="container">
            <h2 className="text-center">Mon Portefeuille</h2>

            <div className="blc-compte bg-beige">
              {/* Solde */}
              <div style={{ textAlign: 'center', padding: '40px 0', background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', borderRadius: '15px', marginBottom: '30px', color: 'white' }}>
                <h3 style={{ color: 'white', marginBottom: '10px' }}>Solde actuel</h3>
                <div style={{ fontSize: '48px', fontWeight: 'bold' }}>{balance.toLocaleString()}</div>
                <div style={{ fontSize: '18px', opacity: 0.9 }}>points de fidélité</div>
                <div style={{ marginTop: '20px' }}>
                  <span style={{ background: 'rgba(255,255,255,0.2)', padding: '8px 20px', borderRadius: '20px' }}>
                    ≈ {(balance * 10).toLocaleString()} MGA
                  </span>
                </div>
              </div>

              {/* Actions rapides */}
              <div className="informations">
                <h3>Actions rapides</h3>
                <div className="row d-flex" style={{ gap: '20px' }}>
                  <Link to="/invite-friend" className="btn" style={{ flex: 1 }}>
                    Parrainer un ami (+300 pts)
                  </Link>
                  <Link to="/ticket-chance" className="btn btn-secondary" style={{ flex: 1 }}>
                    Jouer à Ticket Chance
                  </Link>
                </div>
              </div>

              <div className="sep"></div>

              {/* Comment gagner des points */}
              <div className="informations">
                <h3>Comment gagner des points ?</h3>
                <ul style={{ padding: '0 20px' }}>
                  <li style={{ marginBottom: '10px' }}>✓ <strong>+10 points</strong> pour chaque 1.000 MGA dépensé</li>
                  <li style={{ marginBottom: '10px' }}>✓ <strong>+300 points</strong> pour chaque ami parrainé</li>
                  <li style={{ marginBottom: '10px' }}>✓ <strong>+500 points</strong> bonus anniversaire</li>
                  <li style={{ marginBottom: '10px' }}>✓ <strong>+100 points</strong> pour chaque avis laissé</li>
                  <li style={{ marginBottom: '10px' }}>✓ <strong>Points aléatoires</strong> avec le mini-jeu Ticket Chance</li>
                </ul>
              </div>

              <div className="sep"></div>

              {/* Historique des transactions */}
              <div className="informations no-border">
                <h3>Historique des transactions</h3>
                {transactions.map(transaction => (
                  <div key={transaction.id} style={{ padding: '15px 0', borderBottom: '1px solid #ddd' }}>
                    <div className="row d-flex">
                      <div className="col50">
                        <div>{transaction.description}</div>
                        <small style={{ color: '#666' }}>
                          {new Date(transaction.date).toLocaleDateString('fr-FR')}
                        </small>
                      </div>
                      <div className="col50" style={{ textAlign: 'right' }}>
                        <strong style={{ color: transaction.type === 'earn' ? 'green' : 'red', fontSize: '18px' }}>
                          {transaction.amount > 0 ? '+' : ''}{transaction.amount} pts
                        </strong>
                      </div>
                    </div>
                  </div>
                ))}

                <div className="pagination" style={{ marginTop: '20px' }}>
                  <ul>
                    <li className="prev"><a href="#"></a></li>
                    <li className="selected"><a href="#">1</a></li>
                    <li><a href="#">2</a></li>
                    <li className="next"><a href="#"></a></li>
                  </ul>
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

export default Wallet;

