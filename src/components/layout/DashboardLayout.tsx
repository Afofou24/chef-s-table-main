import { ReactNode, useState } from 'react';
import { AppSidebar } from './AppSidebar';
import { useAuth } from '@/contexts/AuthContext';
import { Navigate } from 'react-router-dom';
import { Menu, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

interface DashboardLayoutProps {
  children: ReactNode;
}

export function DashboardLayout({ children }: DashboardLayoutProps) {
  const { currentUser } = useAuth();
  const [sidebarOpen, setSidebarOpen] = useState(false);

  if (!currentUser) {
    return <Navigate to="/" replace />;
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Mobile Header */}
      <div className="sticky top-0 z-50 flex h-16 w-full items-center justify-between border-b bg-background px-4 md:hidden shadow-sm">
        <div className="flex items-center gap-2">
          <Button variant="ghost" size="icon" onClick={() => setSidebarOpen(!sidebarOpen)} className="relative z-50">
            {sidebarOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
          </Button>
          <span className="font-display text-lg font-semibold">RestoManager</span>
        </div>
      </div>

      {/* Sidebar with mobile transition */}
      <div className={cn(
        "fixed inset-y-0 left-0 z-40 transform transition-transform duration-300 ease-in-out md:translate-x-0 w-64",
        sidebarOpen ? "translate-x-0" : "-translate-x-full"
      )}>
        <AppSidebar
          className="md:relative md:h-screen w-full h-full"
          onNavClick={() => setSidebarOpen(false)}
        />
        {/* Close button inside sidebar for extra clarity (White on dark sidebar) */}
        {sidebarOpen && (
          <div className="absolute right-2 top-2 md:hidden">
            <Button variant="ghost" size="icon" onClick={() => setSidebarOpen(false)} className="text-sidebar-foreground">
              <X className="h-5 w-5" />
            </Button>
          </div>
        )}
      </div>

      {/* Overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm md:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Main Content */}
      <main className={cn(
        "transition-all duration-300 md:pl-64",
        "pt-0" // Padding handled by mobile header or inner padding
      )}>
        <div className="p-4 md:p-6">
          {children}
        </div>
      </main>
    </div>
  );
}
