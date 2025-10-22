import { useState } from 'react';
import { Link } from 'react-router-dom';
import TicketSelector from './TicketSelector';

function EventCard({ event, showTicketSelector = true }) {
  const [showSelector, setShowSelector] = useState(false);

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    const months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    
    return {
      day: days[date.getDay()],
      dayNumber: date.getDate(),
      month: months[date.getMonth()],
      year: date.getFullYear(),
      time: date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    };
  };

  const dateInfo = formatDate(event.startDate);

  return (
    <div className="item wow fadeInUp" data-wow-delay="300ms">
      <div className="inner">
        <div className="blc-col1">
          <div className="col1">
            <div className="date">
              <span>{dateInfo.day}</span>
              <strong>{dateInfo.dayNumber}</strong>
              <span className="text-upper">{dateInfo.month} {dateInfo.year}</span>
            </div>
            <div className="hour">à {dateInfo.time}</div>
            <div className="code-barre">
              <img src="/images/code1.png" alt="code barre" />
            </div>
          </div>
          <div className="col2">
            <span className="bandeau">{event.category?.name || 'Événement'}</span>
            <h3>{event.title}</h3>
            <div className="adresse">Lieu : {event.location}</div>
            <div className="tarif">
              Tarifs : {event.minPrice?.toLocaleString()} MGA - {event.maxPrice?.toLocaleString()} MGA
            </div>
            <div className="blcBtn">
              <button
                onClick={() => setShowSelector(!showSelector)}
                className="btn ticket"
              >
                Acheter un ticket
              </button>
              <Link to={`/events/${event.id}`} className="btn details">
                détails
              </Link>
            </div>
          </div>
        </div>

        <div className="blc-col2">
          <div className="img">
            <img
              src={event.imageUrl || '/images/img1.png'}
              alt={event.title}
            />
          </div>
        </div>

        {showTicketSelector && showSelector && (
          <div className="blc-col3">
            <TicketSelector
              eventId={event.id}
              ticketTypes={event.ticketTypes}
              onClose={() => setShowSelector(false)}
            />
          </div>
        )}
      </div>
    </div>
  );
}

export default EventCard;





