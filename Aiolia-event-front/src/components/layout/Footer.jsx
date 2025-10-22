import { Link } from 'react-router-dom';

function Footer() {
  return (
    <footer className="footer">
      <div className="container">
        <div className="ftr-top d-flex justify-content-between">
          <div className="logo-ftr">
            <Link to="/">
              <img src="/images/VenteTicket.png" alt="Aiolia-event.mg" />
            </Link>
          </div>
          <div className="menu-ftr">
            <ul>
              <li>
                <Link to="/events" title="Évènements">
                  évènements
                </Link>
              </li>
              <li>
                <Link to="/become-organizer" title="Devenir organisateur">
                  devenir organisateur
                </Link>
              </li>
              <li>
                <Link to="/contact" title="Contact">
                  contact
                </Link>
              </li>
              <li>
                <Link to="/about" title="A propos">
                  à propos
                </Link>
              </li>
            </ul>
          </div>
        </div>
        <div className="ftr-bottom d-flex justify-content-between">
          <div className="left">
            <div className="copy">Tous droits réservés © 2025</div>
            <ul>
              <li>
                <Link to="/legal" title="Mentions légales">
                  Mentions légales
                </Link>
              </li>
              <li>
                <Link to="/privacy" title="Politique de confidentialité">
                  Politique de confidentialité
                </Link>
              </li>
              <li>
                <Link to="/terms" title="Conditions générales d'utilisation">
                  Conditions générales d'utilisation
                </Link>
              </li>
            </ul>
          </div>
          <div className="powered">
            <span>Aiolia-event</span>
          </div>
        </div>
      </div>
    </footer>
  );
}

export default Footer;

