import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';

// Auth
import Login from './pages/Auth/Login';
import Register from './pages/Auth/Register';

// Events
import Home from './pages/Events/Home';
import EventList from './pages/Events/EventList';
import EventDetails from './pages/Events/EventDetails';

// Cart & Checkout
import Cart from './pages/Cart/Cart';
import Checkout from './pages/Checkout/Checkout';
import OrderConfirmation from './pages/Order/OrderConfirmation';

// User
import Profile from './pages/User/Profile';
import MyTickets from './pages/User/MyTickets';
import Wallet from './pages/User/Wallet';
import Statistics from './pages/User/Statistics';
import History from './pages/User/History';
import Favorites from './pages/User/Favorites';
import Calendar from './pages/User/Calendar';

// Games
import TicketChance from './pages/Games/TicketChance';

// Contexts
import { CartProvider } from './contexts/CartContext';
import { AuthProvider } from './contexts/AuthContext';

function App() {
  return (
    <AuthProvider>
      <CartProvider>
        <Router>
          <Routes>
            {/* Page d'accueil */}
            <Route path="/" element={<Home />} />
            
            {/* Authentification */}
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            
            {/* Événements */}
            <Route path="/events" element={<EventList />} />
            <Route path="/events/:id" element={<EventDetails />} />
            
            {/* Panier & Paiement */}
            <Route path="/cart" element={<Cart />} />
            <Route path="/checkout" element={<Checkout />} />
            <Route path="/order-confirmation/:orderId" element={<OrderConfirmation />} />
            
            {/* Profil utilisateur */}
            <Route path="/profile" element={<Profile />} />
            <Route path="/my-tickets" element={<MyTickets />} />
            <Route path="/wallet" element={<Wallet />} />
            <Route path="/statistics" element={<Statistics />} />
            <Route path="/history" element={<History />} />
            <Route path="/favorites" element={<Favorites />} />
            <Route path="/calendar" element={<Calendar />} />
            
            {/* Mini-jeux */}
            <Route path="/ticket-chance" element={<TicketChance />} />
            
            {/* 404 - Redirection vers home */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </Router>
      </CartProvider>
    </AuthProvider>
  );
}

export default App;
