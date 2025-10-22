import { createContext, useContext, useState, useEffect } from 'react';

const CartContext = createContext(null);

export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
};

export const CartProvider = ({ children }) => {
  const [cartItems, setCartItems] = useState([]);
  const [loading, setLoading] = useState(true);

  // Charger le panier depuis localStorage au démarrage
  useEffect(() => {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
      try {
        setCartItems(JSON.parse(savedCart));
      } catch (error) {
        console.error('Erreur lors du chargement du panier:', error);
      }
    }
    setLoading(false);
  }, []);

  // Sauvegarder le panier dans localStorage à chaque modification
  useEffect(() => {
    if (!loading) {
      localStorage.setItem('cart', JSON.stringify(cartItems));
    }
  }, [cartItems, loading]);

  const addToCart = (item) => {
    // Vérifier si l'item existe déjà
    const existingItemIndex = cartItems.findIndex(
      (cartItem) =>
        cartItem.eventId === item.eventId &&
        cartItem.ticketTypeId === item.ticketTypeId
    );

    if (existingItemIndex > -1) {
      // Mettre à jour la quantité
      const updatedCart = [...cartItems];
      updatedCart[existingItemIndex] = {
        ...updatedCart[existingItemIndex],
        adults: updatedCart[existingItemIndex].adults + item.adults,
        children: updatedCart[existingItemIndex].children + item.children
      };
      setCartItems(updatedCart);
    } else {
      // Ajouter un nouvel item
      setCartItems([...cartItems, { ...item, addedAt: new Date().toISOString() }]);
    }
  };

  const removeFromCart = (itemId) => {
    setCartItems(cartItems.filter((item) => item.id !== itemId));
  };

  const updateQuantity = (itemId, adults, children) => {
    const updatedCart = cartItems.map((item) =>
      item.id === itemId ? { ...item, adults, children } : item
    );
    setCartItems(updatedCart);
  };

  const clearCart = () => {
    setCartItems([]);
    localStorage.removeItem('cart');
  };

  const getCartTotal = () => {
    return cartItems.reduce((total, item) => {
      const ticketPrice = item.ticketPrice || 0;
      const totalTickets = (item.adults || 0) + (item.children || 0);
      return total + ticketPrice * totalTickets;
    }, 0);
  };

  const getCartCount = () => {
    return cartItems.reduce((count, item) => {
      return count + (item.adults || 0) + (item.children || 0);
    }, 0);
  };

  const applyPromoCode = async (promoCode) => {
    try {
      // TODO: Appel API pour valider le code promo
      // const response = await api.post('/promo/validate', { code: promoCode });
      
      // Simulation
      return {
        success: true,
        discount: 0.1, // 10% de réduction
        message: 'Code promo appliqué avec succès !'
      };
    } catch (error) {
      return {
        success: false,
        message: 'Code promo invalide'
      };
    }
  };

  const value = {
    cartItems,
    loading,
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    getCartTotal,
    getCartCount,
    applyPromoCode
  };

  return (
    <CartContext.Provider value={value}>
      {!loading && children}
    </CartContext.Provider>
  );
};

export default CartContext;






