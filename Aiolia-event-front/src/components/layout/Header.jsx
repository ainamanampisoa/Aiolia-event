import { Link } from 'react-router-dom';

function Header({ showBanner = false, bannerData = null }) {
  return (
    <header>
      <div className="head">
        <div className="container d-flex justify-content-between">
          <Link to="/" className="logo">
            <img src="/images/logo.png" alt="VenteTicket" />
          </Link>
          <div className="blcMenu d-flex">
            <div className="wrapMenuMobile">
              <div className="menuMobile">
                <div></div>
              </div>
            </div>

            <nav className="menu">
              <ul>
                <li className="selected">
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
              </ul>
              <div className="login">
                <Link to="/login">se connecter</Link>
              </div>
            </nav>

            <div className="panier">
              <Link to="/cart"></Link>
              <span>02</span>
            </div>
          </div>
        </div>
      </div>

      {showBanner && bannerData && (
        <div className="blc-banner page event">
          <div className="banner">
            <div
              className="img"
              style={{ background: `url(${bannerData.image})` }}
            ></div>
            <div className="txt-banner">
              <div className="container">
                <div className="inner">
                  <div className="content">
                    <div className="txt wow fadeIn" data-wow-delay="700ms">
                      <div className="left">
                        <span className="s-titre">{bannerData.subtitle}</span>
                        <h1>{bannerData.title}</h1>
                      </div>
                    </div>
                    {bannerData.showTimer && (
                      <div id="timer">
                        <div id="days">
                          <div></div>
                        </div>
                        <div id="hours"></div>
                        <div id="minutes"></div>
                        <div id="seconds"></div>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </header>
  );
}

export default Header;

