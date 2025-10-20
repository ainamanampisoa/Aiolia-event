import { useState } from 'react';

function TicketSelector({ eventId, ticketTypes = [], onClose }) {
  const [selectedType, setSelectedType] = useState(ticketTypes[0]?.id || '');
  const [adults, setAdults] = useState(1);
  const [children, setChildren] = useState(0);

  const handleIncrement = (type) => {
    if (type === 'adult') {
      setAdults(prev => prev + 1);
    } else {
      setChildren(prev => prev + 1);
    }
  };

  const handleDecrement = (type) => {
    if (type === 'adult' && adults > 0) {
      setAdults(prev => prev - 1);
    } else if (type === 'child' && children > 0) {
      setChildren(prev => prev - 1);
    }
  };

  const handleAddToCart = () => {
    const cartItem = {
      eventId,
      ticketTypeId: selectedType,
      adults,
      children,
      timestamp: new Date().toISOString()
    };
    
    // TODO: Ajouter au panier via Context ou API
    console.log('Ajout au panier:', cartItem);
    
    // TODO: Afficher une notification de succès
    alert(`${adults + children} billet(s) ajouté(s) au panier !`);
    
    if (onClose) onClose();
  };

  return (
    <>
      <h3>Option sur le ticket</h3>
      <div className="type">
        <form onSubmit={(e) => { e.preventDefault(); handleAddToCart(); }}>
          <div className="blc-select">
            <label>Type de ticket</label>
            <select
              value={selectedType}
              onChange={(e) => setSelectedType(e.target.value)}
            >
              {ticketTypes.map(type => (
                <option key={type.id} value={type.id}>
                  {type.name} - {type.price.toLocaleString()} MGA
                </option>
              ))}
              {ticketTypes.length === 0 && (
                <option value="">Aucun billet disponible</option>
              )}
            </select>
          </div>

          <div className="blc-nbr d-flex">
            <div className="col">
              <label>Adulte</label>
              <div className="numbers-row">
                <div
                  className="dec button"
                  onClick={() => handleDecrement('adult')}
                >
                  <span>-</span>
                </div>
                <input
                  type="text"
                  name="qtt"
                  value={adults}
                  readOnly
                  className="qtt"
                />
                <div
                  className="inc button"
                  onClick={() => handleIncrement('adult')}
                >
                  <span>+</span>
                </div>
              </div>
            </div>

            <div className="col">
              <label>
                Enfant{' '}
                <span className="info">
                  <img src="/images/info.png" alt="info" />
                  <div className="tooltip">
                    Un ticket est requis pour les enfants de plus de 10 ans
                  </div>
                </span>
              </label>
              <div className="numbers-row">
                <div
                  className="dec button"
                  onClick={() => handleDecrement('child')}
                >
                  <span>-</span>
                </div>
                <input
                  type="text"
                  name="qtt"
                  value={children}
                  readOnly
                  className="qtt"
                />
                <div
                  className="inc button"
                  onClick={() => handleIncrement('child')}
                >
                  <span>+</span>
                </div>
              </div>
            </div>

            <div className="col btn-ok">
              <button type="submit" className="btn ok">
                OK
              </button>
            </div>
          </div>
        </form>
      </div>
    </>
  );
}

export default TicketSelector;



