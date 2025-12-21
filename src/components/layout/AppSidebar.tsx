import {
  LayoutDashboard,
  Users,
  UtensilsCrossed,
  ClipboardList,
  CreditCard,
  Package,
  Grid3X3,
  ChefHat,
  BarChart3,
  Settings,
  LogOut,
  Database
} from 'lucide-react';
import { NavLink, useLocation } from 'react-router-dom';
import { useAuth } from '@/contexts/AuthContext';
import { cn } from '@/lib/utils';

type UserRole = 'admin' | 'manager' | 'waiter' | 'cook' | 'cashier';

const roleLabels: Record<string, string> = {
  admin: 'Administrateur',
  manager: 'Manager',
  waiter: 'Serveur',
  cook: 'Cuisinier',
  cashier: 'Caissier',
};

interface NavItem {
  icon: React.ElementType;
  label: string;
  path: string;
  roles: UserRole[];
}

const navItems: NavItem[] = [
  { icon: LayoutDashboard, label: 'Tableau de bord', path: '/dashboard', roles: ['admin', 'manager', 'cashier', 'waiter', 'cook'] },
  { icon: Users, label: 'Utilisateurs', path: '/users', roles: ['admin'] },
  { icon: UtensilsCrossed, label: 'Menu', path: '/menu', roles: ['admin', 'manager'] },
  { icon: Grid3X3, label: 'Tables', path: '/tables', roles: ['admin', 'manager', 'waiter'] },
  { icon: ClipboardList, label: 'Commandes', path: '/orders', roles: ['admin', 'manager', 'waiter', 'cashier'] },
  { icon: ChefHat, label: 'Cuisine', path: '/kitchen', roles: ['cook'] },
  { icon: CreditCard, label: 'Paiements', path: '/payments', roles: ['admin', 'manager', 'cashier'] },
  { icon: Package, label: 'Stocks', path: '/inventory', roles: ['admin', 'manager'] },
  { icon: BarChart3, label: 'Rapports', path: '/reports', roles: ['admin', 'manager'] },
  { icon: Database, label: 'Backup', path: '/backup', roles: ['admin'] },
  { icon: Settings, label: 'Paramètres', path: '/settings', roles: ['admin'] },
];

export function AppSidebar() {
  const { currentUser, logout } = useAuth();
  const location = useLocation();

  if (!currentUser) return null;

  const filteredNavItems = navItems.filter(item => {
    // Check if user has at least one of the required roles
    // Using role_codes from API
    const userRoles = currentUser.role_codes || [];
    // If userRoles is empty/undefined, maybe allow nothing or check if he matches 'admin' logic if hardcoded?
    // Actually, let's assume specific roles.
    return item.roles.some(role => userRoles.includes(role));
  });

  return (
    <aside className="fixed left-0 top-0 z-40 h-screen w-64 bg-sidebar border-r border-sidebar-border">
      <div className="flex h-full flex-col">
        {/* Logo */}
        <div className="flex h-16 items-center gap-3 border-b border-sidebar-border px-6">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary">
            <UtensilsCrossed className="h-5 w-5 text-primary-foreground" />
          </div>
          <div>
            <h1 className="font-display text-lg font-semibold text-sidebar-foreground">RestoManager</h1>
          </div>
        </div>

        {/* User Info */}
        <div className="border-b border-sidebar-border px-6 py-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-sidebar-accent text-sidebar-accent-foreground font-medium">
              {currentUser.full_name?.split(' ').map(n => n[0]).join('')}
            </div>
            <div>
              <p className="text-sm font-medium text-sidebar-foreground">{currentUser.full_name}</p>
              <p className="text-xs text-sidebar-foreground/60">
                {currentUser.role_codes?.map(code => roleLabels[code] || code).join(', ') || 'Aucun rôle'}
              </p>
            </div>
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
          {filteredNavItems.map((item) => {
            const Icon = item.icon;
            const isActive = location.pathname === item.path;

            return (
              <NavLink
                key={item.path}
                to={item.path}
                className={cn(
                  'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                    : 'text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground'
                )}
              >
                <Icon className="h-5 w-5" />
                {item.label}
              </NavLink>
            );
          })}
        </nav>

        {/* Logout */}
        <div className="border-t border-sidebar-border p-3">
          <button
            onClick={logout}
            className="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-sidebar-foreground/70 transition-colors hover:bg-destructive/10 hover:text-destructive"
          >
            <LogOut className="h-5 w-5" />
            Déconnexion
          </button>
        </div>
      </div>
    </aside>
  );
}
