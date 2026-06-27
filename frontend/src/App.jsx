import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import AppLayout from './components/AppLayout';
import LoginPage from './pages/LoginPage';
import TicketsPage from './pages/TicketsPage';
import TicketCreatePage from './pages/TicketCreatePage';
import TicketDetailPage from './pages/TicketDetailPage';

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route element={<ProtectedRoute />}>
            <Route element={<AppLayout />}>
              <Route path="/tickets" element={<TicketsPage />} />
              <Route path="/tickets/new" element={<TicketCreatePage />} />
              <Route path="/tickets/:id" element={<TicketDetailPage />} />
            </Route>
          </Route>
          <Route path="/" element={<Navigate to="/tickets" replace />} />
          <Route path="*" element={<Navigate to="/tickets" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
