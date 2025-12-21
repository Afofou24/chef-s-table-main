import { useQuery } from '@tanstack/react-query';
import { useAuth } from '@/contexts/AuthContext';
import api from '@/lib/api';
import { DashboardLayout } from '@/components/layout/DashboardLayout';
import { PageHeader } from '@/components/ui/page-header';
import { StatCard } from '@/components/ui/stat-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  ClipboardList,
  DollarSign,
  Users,
  TrendingUp,
  Clock,
  AlertTriangle,
  Loader2
} from 'lucide-react';
import { StatusBadge } from '@/components/ui/status-badge';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

export default function DashboardPage() {
  const { currentUser } = useAuth();

  // Fetch Daily stats (Payments)
  const { data: dailyResponse, isLoading: statsLoading } = useQuery({
    queryKey: ['daily-summary'],
    queryFn: async () => {
      const { data } = await api.get('payments/daily-summary', { params: { last_24h: 1 } });
      return data;
    }
  });
  // Fetch Weekly Stats for chart
  const { data: weeklyResponse, isLoading: weeklyLoading } = useQuery({
    queryKey: ['weekly-summary'],
    queryFn: async () => {
      const { data } = await api.get('payments/weekly-summary');
      return data;
    }
  });

  // Fetch Low Stock
  const { data: lowStockResponse, isLoading: stockLoading } = useQuery({
    queryKey: ['stock-low'],
    queryFn: async () => {
      const { data } = await api.get('stock/low'); // Note: Endpoint exists in Controller as lowStock()
      return data;
    }
  });

  // Fetch Tables
  const { data: tablesResponse, isLoading: tablesLoading } = useQuery({
    queryKey: ['tables-dashboard'],
    queryFn: async () => {
      const { data } = await api.get('tables');
      return data;
    }
  });

  // Fetch Recent Orders
  const { data: ordersResponse, isLoading: ordersLoading } = useQuery({
    queryKey: ['recent-orders'],
    queryFn: async () => {
      const { data } = await api.get('orders', { params: { per_page: 5 } });
      return data;
    }
  });

  const stats = dailyResponse || { total_amount: 0, total_count: 0 };
  const lowStockItems = Array.isArray(lowStockResponse) ? lowStockResponse : [];
  const tables = tablesResponse?.data || [];
  const activeOrders = ordersResponse?.data?.filter((o: any) => o.status === 'pending' || o.status === 'preparing') || [];
  const availableTables = tables.filter((t: any) => t.status === 'available').length;

  // Map backend weekly summary to chart days
  const dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
  const today = new Date();

  const weeklyRevenue = Array.from({ length: 7 }).map((_, i) => {
    const d = new Date();
    d.setDate(today.getDate() - (6 - i));
    const dateStr = d.toISOString().split('T')[0];
    const dayName = dayNames[d.getDay()];

    // Find entry in weeklyResponse
    const entry = Array.isArray(weeklyResponse)
      ? weeklyResponse.find((e: any) => e.date === dateStr)
      : null;

    return {
      day: dayName,
      revenue: entry ? Number(entry.total) : 0
    };
  });

  const isLoading = statsLoading || stockLoading || tablesLoading || ordersLoading || weeklyLoading;

  if (isLoading) {
    return (
      <DashboardLayout>
        <div className="flex items-center justify-center min-h-[60vh]">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
        </div>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout>
      <PageHeader
        title={`Bonjour, ${currentUser?.username || 'Utilisateur'}`}
        description="Voici un aperçu de l'activité du restaurant aujourd'hui"
      />

      {/* Stats Grid */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
        <StatCard
          title="Commandes du jour"
          value={stats.total_count}
          icon={<ClipboardList className="h-6 w-6" />}
          trend={{ value: 0, isPositive: true }}
        />
        <StatCard
          title="Chiffre d'affaires"
          value={`${Number(stats.total_amount).toFixed(2)} €`}
          icon={<DollarSign className="h-6 w-6" />}
          trend={{ value: 0, isPositive: true }}
        />
        <StatCard
          title="Ticket moyen"
          value={`${(stats.total_count > 0 ? stats.total_amount / stats.total_count : 0).toFixed(2)} €`}
          icon={<TrendingUp className="h-6 w-6" />}
          trend={{ value: 0, isPositive: true }}
        />
        <StatCard
          title="Tables disponibles"
          value={`${availableTables}/${tables.length}`}
          icon={<Users className="h-6 w-6" />}
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Revenue Chart */}
        <Card>
          <CardHeader>
            <CardTitle className="font-display">Revenus de la semaine</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="h-[300px]">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={weeklyRevenue}>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                  <XAxis dataKey="day" className="text-xs" />
                  <YAxis className="text-xs" />
                  <Tooltip
                    contentStyle={{
                      backgroundColor: 'hsl(var(--card))',
                      border: '1px solid hsl(var(--border))',
                      borderRadius: '8px'
                    }}
                    formatter={(value) => [`${value} €`, 'Revenus']}
                  />
                  <Bar dataKey="revenue" fill="hsl(var(--primary))" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        {/* Low Stock Alert */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="font-display">Alertes stock</CardTitle>
            <AlertTriangle className="h-5 w-5 text-warning" />
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {lowStockItems.length === 0 ? (
                <p className="text-sm text-muted-foreground text-center py-4">
                  Tous les stocks sont suffisants
                </p>
              ) : (
                lowStockItems.map((item: any) => (
                  <div key={item.id} className="flex items-center justify-between p-3 rounded-lg bg-destructive/5 border border-destructive/20">
                    <div>
                      <p className="font-medium text-foreground">{item.name}</p>
                      <p className="text-sm text-muted-foreground">{item.supplier || 'Sans fournisseur'}</p>
                    </div>
                    <div className="text-right">
                      <p className="font-semibold text-destructive">
                        {item.quantity} {item.unit}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        Min: {item.min_quantity} {item.unit}
                      </p>
                    </div>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        </Card>

        {/* Active Orders */}
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="font-display">Commandes récentes en cours</CardTitle>
            <Clock className="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="grid gap-3 md:grid-cols-2">
              {activeOrders.length === 0 ? (
                <p className="text-sm text-muted-foreground text-center py-4 col-span-2">
                  Aucune commande en cours
                </p>
              ) : (
                activeOrders.map((order: any) => (
                  <div key={order.id} className="flex items-center justify-between p-3 rounded-lg bg-muted/50">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-background font-semibold">
                        T{order.table?.number || '?'}
                      </div>
                      <div>
                        <p className="font-medium">{order.order_number}</p>
                        <p className="text-sm text-muted-foreground">{order.waiter?.first_name || 'Utilisateur'}</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="font-semibold">{Number(order.total_amount).toFixed(2)} €</p>
                      <StatusBadge status={order.status === 'pending' ? 'warning' : 'info'}>
                        {order.status === 'pending' ? 'En attente' : 'En préparation'}
                      </StatusBadge>
                    </div>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  );
}
