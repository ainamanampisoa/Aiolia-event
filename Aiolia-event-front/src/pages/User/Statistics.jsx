import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';

function Statistics() {
  const stats = {
    totalEvents: 24,
    totalSpent: 3850000,
    favoriteCategory: 'Concert',
    upcomingEvents: 3,
    monthlySpending: [
      { month: 'Jan', amount: 250000 },
      { month: 'Fév', amount: 320000 },
      { month: 'Mar', amount: 450000 },
      { month: 'Avr', amount: 380000 },
      { month: 'Mai', amount: 520000 },
      { month: 'Juin', amount: 410000 },
    ],
    categories: [
      { name: 'Concert', count: 10, percentage: 42 },
      { name: 'Soirée live', count: 8, percentage: 33 },
      { name: 'Festival', count: 4, percentage: 17 },
      { name: 'Conférence', count: 2, percentage: 8 },
    ]
  };

  return (
    <>
      <Header showBanner={false} />

      <main>
        <section className="sec-new-account pt-100 pb-100">
          <div className="container">
            <h2 className="text-center">Mes Statistiques</h2>

            <div className="blc-compte bg-beige">
              {/* Statistiques globales */}
              <div className="informations">
                <h3>Vue d'ensemble</h3>
                <div className="lstAtout d-flex" style={{ flexWrap: 'wrap' }}>
                  <div className="item" style={{ flex: '1 1 200px', margin: '10px' }}>
                    <div className="inner" style={{ background: '#f0f9ff', padding: '30px', borderRadius: '10px' }}>
                      <div className="ico">
                        <img src="/images/ico4.svg" alt="Events" />
                      </div>
                      <strong className="count" style={{ fontSize: '36px', color: '#1F2D3D' }}>
                        {stats.totalEvents}
                      </strong>
                      <span>Événements assistés</span>
                    </div>
                  </div>

                  <div className="item" style={{ flex: '1 1 200px', margin: '10px' }}>
                    <div className="inner" style={{ background: '#fef3c7', padding: '30px', borderRadius: '10px' }}>
                      <div className="ico">
                        <img src="/images/ico5.svg" alt="Money" />
                      </div>
                      <strong className="count" style={{ fontSize: '28px', color: '#1F2D3D' }}>
                        {stats.totalSpent.toLocaleString()} MGA
                      </strong>
                      <span>Dépenses totales</span>
                    </div>
                  </div>

                  <div className="item" style={{ flex: '1 1 200px', margin: '10px' }}>
                    <div className="inner" style={{ background: '#d1fae5', padding: '30px', borderRadius: '10px' }}>
                      <div className="ico">
                        <img src="/images/ico6.svg" alt="Category" />
                      </div>
                      <strong className="count" style={{ fontSize: '24px', color: '#1F2D3D' }}>
                        {stats.favoriteCategory}
                      </strong>
                      <span>Catégorie préférée</span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="sep"></div>

              {/* Dépenses mensuelles */}
              <div className="informations">
                <h3>Dépenses mensuelles (2025)</h3>
                <div style={{ padding: '20px', background: '#f9f9f9', borderRadius: '10px' }}>
                  {stats.monthlySpending.map((data, index) => (
                    <div key={index} style={{ marginBottom: '15px' }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '5px' }}>
                        <span>{data.month}</span>
                        <strong>{data.amount.toLocaleString()} MGA</strong>
                      </div>
                      <div style={{ width: '100%', height: '10px', background: '#e0e0e0', borderRadius: '5px', overflow: 'hidden' }}>
                        <div style={{
                          width: `${(data.amount / 600000) * 100}%`,
                          height: '100%',
                          background: 'linear-gradient(90deg, #667eea 0%, #764ba2 100%)',
                          transition: 'width 0.3s ease'
                        }}></div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="sep"></div>

              {/* Répartition par catégories */}
              <div className="informations no-border">
                <h3>Événements par catégorie</h3>
                {stats.categories.map((category, index) => (
                  <div key={index} style={{ marginBottom: '20px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                      <span><strong>{category.name}</strong></span>
                      <span>{category.count} événements ({category.percentage}%)</span>
                    </div>
                    <div style={{ width: '100%', height: '12px', background: '#e0e0e0', borderRadius: '6px', overflow: 'hidden' }}>
                      <div style={{
                        width: `${category.percentage}%`,
                        height: '100%',
                        background: `hsl(${index * 60}, 70%, 60%)`,
                        transition: 'width 0.3s ease'
                      }}></div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </>
  );
}

export default Statistics;

