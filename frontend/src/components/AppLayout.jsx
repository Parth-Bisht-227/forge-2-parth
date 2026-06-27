import { Link, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function AppLayout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-14">
            <div className="flex items-center gap-6">
              <Link to="/" className="text-lg font-bold text-gray-900">
                PulseDesk
              </Link>
              <Link to="/tickets" className="text-sm text-gray-600 hover:text-gray-900">
                Tickets
              </Link>
            </div>
            <div className="flex items-center gap-4">
              <span className="text-sm text-gray-600">{user?.name}</span>
              <span className="text-xs rounded-full bg-indigo-100 text-indigo-700 px-2 py-0.5 capitalize">
                {user?.role}
              </span>
              <button
                onClick={handleLogout}
                className="text-sm text-gray-600 hover:text-gray-900"
              >
                Log out
              </button>
            </div>
          </div>
        </div>
      </nav>
      <main className="max-w-7xl mx-auto">
        <Outlet />
      </main>
    </div>
  );
}
