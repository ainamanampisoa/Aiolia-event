/**
 * ============================================================================
 * AIOLIA EVENT - Fichier de Traductions Multi-Langues
 * ============================================================================
 * Langues supportées: Français (fr), English (en), Malagasy (mg)
 * Usage: Import dans votre application frontend/backend
 * ============================================================================
 */

const translations = {
  
  // ===========================================================================
  // CATÉGORIES D'ÉVÉNEMENTS
  // ===========================================================================
  event_categories: {
    concert: {
      fr: {
        name: "Concert",
        description: "Concerts et spectacles musicaux"
      },
      en: {
        name: "Concert",
        description: "Concerts and musical shows"
      },
      mg: {
        name: "Fampisehoana mozika",
        description: "Fampisehoana mozika"
      }
    },
    conference: {
      fr: {
        name: "Conférence",
        description: "Conférences et séminaires"
      },
      en: {
        name: "Conference",
        description: "Conferences and seminars"
      },
      mg: {
        name: "Fihaonambe",
        description: "Fihaonambe sy seminera"
      }
    },
    sport: {
      fr: {
        name: "Sport",
        description: "Événements sportifs"
      },
      en: {
        name: "Sports",
        description: "Sports events"
      },
      mg: {
        name: "Fanatanjahantena",
        description: "Hetsika ara-panatanjahantena"
      }
    },
    festival: {
      fr: {
        name: "Festival",
        description: "Festivals et célébrations"
      },
      en: {
        name: "Festival",
        description: "Festivals and celebrations"
      },
      mg: {
        name: "Fetibe",
        description: "Fetibe sy fankalazana"
      }
    },
    theatre: {
      fr: {
        name: "Théâtre",
        description: "Pièces de théâtre"
      },
      en: {
        name: "Theater",
        description: "Theater plays"
      },
      mg: {
        name: "Teatra",
        description: "Teatra"
      }
    },
    formation: {
      fr: {
        name: "Formation",
        description: "Formations et ateliers"
      },
      en: {
        name: "Training",
        description: "Training and workshops"
      },
      mg: {
        name: "Fiofanana",
        description: "Fiofanana sy atrikasa"
      }
    },
    networking: {
      fr: {
        name: "Networking",
        description: "Événements de réseautage"
      },
      en: {
        name: "Networking",
        description: "Networking events"
      },
      mg: {
        name: "Fifandraisana",
        description: "Hetsika fifandraisana"
      }
    },
    autre: {
      fr: {
        name: "Autre",
        description: "Autres types d'événements"
      },
      en: {
        name: "Other",
        description: "Other event types"
      },
      mg: {
        name: "Hafa",
        description: "Karazana hetsika hafa"
      }
    }
  },

  // ===========================================================================
  // INTERFACE UTILISATEUR - MODULE 1 (UTILISATEURS)
  // ===========================================================================
  ui: {
    // Navigation
    nav: {
      home: {
        fr: "Accueil",
        en: "Home",
        mg: "Fandraisana"
      },
      events: {
        fr: "Événements",
        en: "Events",
        mg: "Hetsika"
      },
      my_tickets: {
        fr: "Mes billets",
        en: "My tickets",
        mg: "Ny tapakila"
      },
      favorites: {
        fr: "Favoris",
        en: "Favorites",
        mg: "Tiako"
      },
      profile: {
        fr: "Profil",
        en: "Profile",
        mg: "Mombamomba"
      },
      logout: {
        fr: "Déconnexion",
        en: "Logout",
        mg: "Hiala"
      }
    },

    // Recherche & Filtres
    search: {
      placeholder: {
        fr: "Rechercher un événement...",
        en: "Search for an event...",
        mg: "Mitady hetsika..."
      },
      filter_by_category: {
        fr: "Filtrer par catégorie",
        en: "Filter by category",
        mg: "Sivana araka ny karazany"
      },
      filter_by_date: {
        fr: "Filtrer par date",
        en: "Filter by date",
        mg: "Sivana araka ny daty"
      },
      filter_by_location: {
        fr: "Filtrer par lieu",
        en: "Filter by location",
        mg: "Sivana araka ny toerana"
      },
      filter_by_price: {
        fr: "Filtrer par prix",
        en: "Filter by price",
        mg: "Sivana araka ny vidiny"
      },
      no_results: {
        fr: "Aucun résultat trouvé",
        en: "No results found",
        mg: "Tsy misy vokatra"
      }
    },

    // Billetterie
    tickets: {
      select_quantity: {
        fr: "Sélectionner la quantité",
        en: "Select quantity",
        mg: "Safidio ny isa"
      },
      category: {
        fr: "Catégorie",
        en: "Category",
        mg: "Karazana"
      },
      price: {
        fr: "Prix",
        en: "Price",
        mg: "Vidiny"
      },
      available: {
        fr: "Disponible",
        en: "Available",
        mg: "Misy"
      },
      sold_out: {
        fr: "Complet",
        en: "Sold out",
        mg: "Lany"
      },
      add_to_cart: {
        fr: "Ajouter au panier",
        en: "Add to cart",
        mg: "Ampidirina"
      },
      qr_code: {
        fr: "Code QR",
        en: "QR Code",
        mg: "Kaody QR"
      },
      download_ticket: {
        fr: "Télécharger le billet",
        en: "Download ticket",
        mg: "Alaina ny tapakila"
      },
      transfer_ticket: {
        fr: "Transférer le billet",
        en: "Transfer ticket",
        mg: "Afindra ny tapakila"
      }
    },

    // Panier
    cart: {
      title: {
        fr: "Panier",
        en: "Cart",
        mg: "Harona"
      },
      empty: {
        fr: "Votre panier est vide",
        en: "Your cart is empty",
        mg: "Tsy misy ao amin'ny harona"
      },
      subtotal: {
        fr: "Sous-total",
        en: "Subtotal",
        mg: "Totaly"
      },
      discount: {
        fr: "Réduction",
        en: "Discount",
        mg: "Fihenam-bidy"
      },
      total: {
        fr: "Total",
        en: "Total",
        mg: "Totalin'ny rehetra"
      },
      proceed_to_checkout: {
        fr: "Procéder au paiement",
        en: "Proceed to checkout",
        mg: "Handoa"
      },
      apply_promo: {
        fr: "Appliquer un code promo",
        en: "Apply promo code",
        mg: "Mampiasa kaody fihenam-bidy"
      }
    },

    // Paiement
    payment: {
      choose_method: {
        fr: "Choisir un moyen de paiement",
        en: "Choose payment method",
        mg: "Safidio ny fandoavana"
      },
      orange_money: {
        fr: "Orange Money",
        en: "Orange Money",
        mg: "Orange Money"
      },
      airtel_money: {
        fr: "Airtel Money",
        en: "Airtel Money",
        mg: "Airtel Money"
      },
      mvola: {
        fr: "MVola",
        en: "MVola",
        mg: "MVola"
      },
      bank_card: {
        fr: "Carte bancaire",
        en: "Bank card",
        mg: "Karatra"
      },
      confirm_payment: {
        fr: "Confirmer le paiement",
        en: "Confirm payment",
        mg: "Hamafa ny fandoavana"
      },
      payment_success: {
        fr: "Paiement réussi !",
        en: "Payment successful!",
        mg: "Vita ny fandoavana!"
      },
      payment_failed: {
        fr: "Échec du paiement",
        en: "Payment failed",
        mg: "Tsy lany ny fandoavana"
      }
    },

    // Profil
    profile: {
      my_profile: {
        fr: "Mon profil",
        en: "My profile",
        mg: "Ny mombamomba"
      },
      edit_profile: {
        fr: "Modifier le profil",
        en: "Edit profile",
        mg: "Hanova ny mombamomba"
      },
      statistics: {
        fr: "Statistiques",
        en: "Statistics",
        mg: "Antontan'isa"
      },
      wallet: {
        fr: "Portefeuille",
        en: "Wallet",
        mg: "Kitapom-bola"
      },
      loyalty_points: {
        fr: "Points de fidélité",
        en: "Loyalty points",
        mg: "Isa tsy mivadika"
      },
      referral_code: {
        fr: "Code de parrainage",
        en: "Referral code",
        mg: "Kaody fampitaovana"
      },
      invite_friends: {
        fr: "Inviter des amis",
        en: "Invite friends",
        mg: "Manasa namana"
      }
    },

    // Notifications
    notifications: {
      title: {
        fr: "Notifications",
        en: "Notifications",
        mg: "Fampandrenesana"
      },
      mark_as_read: {
        fr: "Marquer comme lu",
        en: "Mark as read",
        mg: "Vita ny famakiana"
      },
      no_notifications: {
        fr: "Aucune notification",
        en: "No notifications",
        mg: "Tsy misy fampandrenesana"
      }
    },

    // Statuts
    status: {
      valid: {
        fr: "Valide",
        en: "Valid",
        mg: "Manan-kery"
      },
      used: {
        fr: "Utilisé",
        en: "Used",
        mg: "Efa nampiasaina"
      },
      cancelled: {
        fr: "Annulé",
        en: "Cancelled",
        mg: "Nesorina"
      },
      pending: {
        fr: "En attente",
        en: "Pending",
        mg: "Miandry"
      },
      completed: {
        fr: "Terminé",
        en: "Completed",
        mg: "Vita"
      }
    }
  },

  // ===========================================================================
  // INTERFACE ORGANISATEUR - MODULE 2
  // ===========================================================================
  organizer: {
    // Dashboard
    dashboard: {
      title: {
        fr: "Tableau de bord",
        en: "Dashboard",
        mg: "Fizaran'ny asam-panjakana"
      },
      my_events: {
        fr: "Mes événements",
        en: "My events",
        mg: "Ny hetsika"
      },
      create_event: {
        fr: "Créer un événement",
        en: "Create event",
        mg: "Hamorona hetsika"
      },
      statistics: {
        fr: "Statistiques",
        en: "Statistics",
        mg: "Antontan'isa"
      },
      sales: {
        fr: "Ventes",
        en: "Sales",
        mg: "Varotra"
      },
      revenue: {
        fr: "Revenus",
        en: "Revenue",
        mg: "Vola miditra"
      }
    },

    // Gestion événements
    events: {
      event_details: {
        fr: "Détails de l'événement",
        en: "Event details",
        mg: "Antsipirihan'ny hetsika"
      },
      edit_event: {
        fr: "Modifier l'événement",
        en: "Edit event",
        mg: "Hanova ny hetsika"
      },
      delete_event: {
        fr: "Supprimer l'événement",
        en: "Delete event",
        mg: "Hamafa ny hetsika"
      },
      publish_event: {
        fr: "Publier l'événement",
        en: "Publish event",
        mg: "Hamoaka ny hetsika"
      },
      upload_media: {
        fr: "Télécharger des médias",
        en: "Upload media",
        mg: "Hampiditra media"
      },
      manage_team: {
        fr: "Gérer l'équipe",
        en: "Manage team",
        mg: "Mitantana ekipa"
      },
      co_organizers: {
        fr: "Co-organisateurs",
        en: "Co-organizers",
        mg: "Mpiara-mikarakara"
      }
    },

    // Billetterie
    tickets: {
      manage_tickets: {
        fr: "Gérer les billets",
        en: "Manage tickets",
        mg: "Mitantana ny tapakila"
      },
      ticket_categories: {
        fr: "Catégories de billets",
        en: "Ticket categories",
        mg: "Karazana tapakila"
      },
      create_category: {
        fr: "Créer une catégorie",
        en: "Create category",
        mg: "Hamorona karazana"
      },
      sold: {
        fr: "Vendus",
        en: "Sold",
        mg: "Amidy"
      },
      available: {
        fr: "Disponibles",
        en: "Available",
        mg: "Misy"
      },
      reserved: {
        fr: "Réservés",
        en: "Reserved",
        mg: "Voatahiry"
      },
      low_stock_alert: {
        fr: "Alerte stock faible",
        en: "Low stock alert",
        mg: "Fampandrenesana fa vitsy"
      },
      waitlist: {
        fr: "Liste d'attente",
        en: "Waitlist",
        mg: "Lisitra fiandrasana"
      }
    },

    // Codes promo
    promo: {
      promo_codes: {
        fr: "Codes promo",
        en: "Promo codes",
        mg: "Kaody fihenam-bidy"
      },
      create_promo: {
        fr: "Créer un code promo",
        en: "Create promo code",
        mg: "Hamorona kaody fihenam-bidy"
      },
      discount_type: {
        fr: "Type de réduction",
        en: "Discount type",
        mg: "Karazana fihenam-bidy"
      },
      percentage: {
        fr: "Pourcentage",
        en: "Percentage",
        mg: "Isan-jato"
      },
      fixed_amount: {
        fr: "Montant fixe",
        en: "Fixed amount",
        mg: "Vidiny raikitra"
      },
      uses: {
        fr: "Utilisations",
        en: "Uses",
        mg: "Fampiasana"
      }
    },

    // Statistiques
    statistics: {
      total_tickets_sold: {
        fr: "Total billets vendus",
        en: "Total tickets sold",
        mg: "Tapakila amidy rehetra"
      },
      total_revenue: {
        fr: "Revenu total",
        en: "Total revenue",
        mg: "Vola miditra rehetra"
      },
      average_ticket_price: {
        fr: "Prix moyen du billet",
        en: "Average ticket price",
        mg: "Vidin'ny tapakila antonony"
      },
      conversion_rate: {
        fr: "Taux de conversion",
        en: "Conversion rate",
        mg: "Tahan'ny fiovam-po"
      },
      views: {
        fr: "Vues",
        en: "Views",
        mg: "Fijerena"
      },
      favorites: {
        fr: "Favoris",
        en: "Favorites",
        mg: "Tiako"
      },
      export_csv: {
        fr: "Exporter en CSV",
        en: "Export to CSV",
        mg: "Avoaka CSV"
      },
      export_pdf: {
        fr: "Exporter en PDF",
        en: "Export to PDF",
        mg: "Avoaka PDF"
      }
    }
  },

  // ===========================================================================
  // EMAILS
  // ===========================================================================
  emails: {
    order_confirmation: {
      subject: {
        fr: "Confirmation de votre commande",
        en: "Order confirmation",
        mg: "Fanamafisana ny baiko"
      },
      body: {
        fr: "Merci pour votre achat !",
        en: "Thank you for your purchase!",
        mg: "Misaotra tamin'ny fividianana!"
      }
    },
    event_reminder: {
      subject: {
        fr: "Rappel : Votre événement commence bientôt",
        en: "Reminder: Your event starts soon",
        mg: "Fampahatsiahy: Manomboka tsy ho ela ny hetsika"
      }
    },
    ticket_transferred: {
      subject: {
        fr: "Transfert de billet",
        en: "Ticket transfer",
        mg: "Famindrana tapakila"
      }
    }
  },

  // ===========================================================================
  // MESSAGES D'ERREUR
  // ===========================================================================
  errors: {
    generic: {
      fr: "Une erreur s'est produite",
      en: "An error occurred",
      mg: "Nisy olana"
    },
    not_found: {
      fr: "Non trouvé",
      en: "Not found",
      mg: "Tsy hita"
    },
    unauthorized: {
      fr: "Non autorisé",
      en: "Unauthorized",
      mg: "Tsy manana alalana"
    },
    invalid_credentials: {
      fr: "Identifiants invalides",
      en: "Invalid credentials",
      mg: "Tsy mety ny fampidirana"
    },
    sold_out: {
      fr: "Billets épuisés",
      en: "Tickets sold out",
      mg: "Lany ny tapakila"
    },
    payment_failed: {
      fr: "Échec du paiement",
      en: "Payment failed",
      mg: "Tsy lany ny fandoavana"
    }
  },

  // ===========================================================================
  // MESSAGES DE SUCCÈS
  // ===========================================================================
  success: {
    event_created: {
      fr: "Événement créé avec succès",
      en: "Event created successfully",
      mg: "Voaforona soa aman-tsara ny hetsika"
    },
    ticket_purchased: {
      fr: "Billet acheté avec succès",
      en: "Ticket purchased successfully",
      mg: "Voavidiana soa aman-tsara ny tapakila"
    },
    profile_updated: {
      fr: "Profil mis à jour",
      en: "Profile updated",
      mg: "Novaina ny mombamomba"
    }
  }
};

// ===========================================================================
// FONCTION HELPER POUR OBTENIR UNE TRADUCTION
// ===========================================================================

/**
 * Obtient une traduction pour une clé donnée
 * @param {string} key - Clé de traduction (ex: "ui.nav.home")
 * @param {string} lang - Code langue (fr, en, mg)
 * @param {object} params - Paramètres pour interpolation
 * @returns {string} - Texte traduit
 */
function t(key, lang = 'fr', params = {}) {
  const keys = key.split('.');
  let value = translations;
  
  for (const k of keys) {
    value = value[k];
    if (!value) return key; // Retourne la clé si non trouvée
  }
  
  const translation = value[lang] || value['fr'] || key;
  
  // Interpolation de paramètres {param}
  return Object.keys(params).reduce((str, param) => {
    return str.replace(`{${param}}`, params[param]);
  }, translation);
}

// ===========================================================================
// EXPORT
// ===========================================================================

// Pour Node.js / CommonJS
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { translations, t };
}

// Pour ES6 Modules
export { translations, t };

// Pour usage global dans le browser
if (typeof window !== 'undefined') {
  window.AioliaTranslations = { translations, t };
}

