import { Link } from 'react-router-dom';

function Header({ showBanner = false, bannerData = null }) {
  return (
    <header>
      <div className="head">
        <div className="container d-flex justify-content-between">
          <Link to="/" className="logo">
            <img src="/images/aiolia_event.png" alt="VenteTicket" />
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
                  <Link to="/" title="Évènements">
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
        <div className="blc-banner">
          <div className="banner">
            <div
              className="img"
              style={{ background: `url(${bannerData.image})` }}
            ></div>
            <div className="txt-banner">
              <div className="container">
                <div className="inner">
                  <div className="content">
                    {bannerData.logo && (
                      <div className="img-logo wow fadeIn" data-wow-delay="500ms">
                        <img src={bannerData.logo} alt="Vente tickets" />
                      </div>
                    )}
                    <div className="txt wow fadeIn" data-wow-delay="700ms">
                      <h1>{bannerData.title}</h1>
                      {bannerData.address && (
                        <div className="adresse">{bannerData.address}</div>
                      )}
                      {bannerData.description && (
                        <p>{bannerData.description}</p>
                      )}
                      {bannerData.contact && (
                        <div className="contact">
                          <ul>
                            {bannerData.contact.phone && (
                              <li className="tel">
                                <a href={`tel:${bannerData.contact.phone.replace(/\s/g, '')}`}>
                                  <span>{bannerData.contact.phone}</span>
                                </a>
                              </li>
                            )}
                            {bannerData.contact.email && (
                              <li className="mail">
                                <a href={`mailto:${bannerData.contact.email}`}>
                                  <span>{bannerData.contact.email}</span>
                                </a>
                              </li>
                            )}
                            {bannerData.contact.website && (
                              <li className="web">
                                <a href={`https://${bannerData.contact.website}`} target="_blank" rel="noopener noreferrer">
                                  <span>{bannerData.contact.website}</span>
                                </a>
                              </li>
                            )}
                          </ul>
                        </div>
                      )}
                    </div>
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

