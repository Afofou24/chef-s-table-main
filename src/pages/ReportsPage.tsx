import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';
import { DashboardLayout } from '@/components/layout/DashboardLayout';
import { PageHeader } from '@/components/ui/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Download, TrendingUp, DollarSign, ShoppingCart, Users, Loader2 } from 'lucide-react';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  LineChart, Line, PieChart, Pie, Cell
} from 'recharts';

const COLORS = ['hsl(var(--primary))', 'hsl(var(--secondary))', 'hsl(var(--accent))', 'hsl(var(--success))', 'hsl(var(--warning))'];

export default function ReportsPage() {

  // Fetch Daily stats (Payments)
  const { data: dailyResponse, isLoading: dailyLoading } = useQuery({
    queryKey: ['reports-daily-summary'],
    queryFn: async () => {
      const { data } = await api.get('/payments/daily-summary', { params: { last_24h: 1 } });
      return data;
    }
  });

  // Fetch Category Distribution
  const { data: categoryResponse, isLoading: categoryLoading } = useQuery({
    queryKey: ['reports-category-revenue'],
    queryFn: async () => {
      const { data } = await api.get('/reports/revenue-by-category');
      return data;
    }
  });

  // Fetch Popular Items
  const { data: popularResponse, isLoading: itemsLoading } = useQuery({
    queryKey: ['reports-popular-items'],
    queryFn: async () => {
      const { data } = await api.get('/reports/popular-items');
      return data;
    }
  });

  // Fetch Weekly Stats for chart
  const { data: weeklyResponse, isLoading: weeklyLoading } = useQuery({
    queryKey: ['reports-weekly-summary'],
    queryFn: async () => {
      const { data } = await api.get('/payments/weekly-summary');
      return data;
    }
  });

  const stats = dailyResponse || { total_amount: 0, total_count: 0 };

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

  // Ensure data types are correct for Recharts
  const categoryRevenue = Array.isArray(categoryResponse)
    ? categoryResponse.map((item: any) => ({ ...item, value: Number(item.value) }))
    : [];

  const popularDishes = Array.isArray(popularResponse)
    ? popularResponse.map((item: any) => ({ ...item, orders: Number(item.orders) }))
    : [];

  if (dailyLoading || weeklyLoading || categoryLoading || itemsLoading) {
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
        title="Rapports & Statistiques"
        description="Analysez les performances réelles de votre restaurant"
        actions={
          <div className="flex gap-3">
            <Select defaultValue="today">
              <SelectTrigger className="w-40">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="today">Aujourd'hui</SelectItem>
                <SelectItem value="week">Cette semaine (Bientôt)</SelectItem>
              </SelectContent>
            </Select>
            <Button variant="outline" onClick={() => window.print()}>
              <Download className="h-4 w-4 mr-2" />
              Exporter
            </Button>
          </div>
        }
      />

      {/* KPI Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <DollarSign className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Chiffre d'affaires</p>
                <p className="text-2xl font-bold">{Number(stats.total_amount).toFixed(2)} €</p>
                <p className="text-[10px] text-green-500 font-medium">+12% par rapport à la semaine dernière</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-success/10 text-success">
                <ShoppingCart className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Commandes</p>
                <p className="text-2xl font-bold">{stats.total_count}</p>
                <p className="text-[10px] text-green-500 font-medium">+8% par rapport à la semaine dernière</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-warning/10 text-warning">
                <TrendingUp className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Billet moyen</p>
                <p className="text-2xl font-bold">{(stats.total_count > 0 ? stats.total_amount / stats.total_count : 0).toFixed(2)} €</p>
                <p className="text-[10px] text-green-500 font-medium">+3% par rapport à la semaine dernière</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-chart-3/10 text-chart-3">
                <Users className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Tables servies</p>
                <p className="text-2xl font-bold">{stats.total_count}</p>
                <p className="text-[10px] text-green-500 font-medium">+5% par rapport à la semaine dernière</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2 mb-6">
        {/* Revenue Trend */}
        <Card>
          <CardHeader>
            <CardTitle className="font-display text-xl">Évolution des revenus</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="h-[300px]">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={weeklyRevenue}>
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
                  <Line
                    type="monotone"
                    dataKey="revenue"
                    stroke="hsl(var(--primary))"
                    strokeWidth={2}
                    dot={{ fill: 'hsl(var(--primary))' }}
                  />
                </LineChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        {/* Revenue by Category */}
        <Card>
          <CardHeader>
            <CardTitle className="font-display text-xl">Revenus par catégorie</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="h-[300px]">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={categoryRevenue}
                    cx="50%"
                    cy="50%"
                    innerRadius={70}
                    outerRadius={100}
                    paddingAngle={5}
                    dataKey="value"
                    nameKey="name"
                    label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                    isAnimationActive={true}
                    animationBegin={0}
                    animationDuration={1500}
                  >
                    {categoryRevenue.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip
                    contentStyle={{
                      backgroundColor: 'hsl(var(--card))',
                      border: '1px solid hsl(var(--border))',
                      borderRadius: '8px',
                      boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
                    }}
                    formatter={(value) => [`${(Number(value)).toFixed(2)} €`, 'Revenus']}
                  />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Top Dishes */}
      <Card>
        <CardHeader>
          <CardTitle className="font-display text-xl">Top 5 des plats</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="h-[300px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={popularDishes} layout="vertical">
                <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                <XAxis type="number" className="text-xs" />
                <YAxis dataKey="name" type="category" width={150} className="text-xs" />
                <Tooltip
                  contentStyle={{
                    backgroundColor: 'hsl(var(--card))',
                    border: '1px solid hsl(var(--border))',
                    borderRadius: '8px'
                  }}
                  formatter={(value, name) => [
                    `${value} unités`,
                    'Volume'
                  ]}
                />
                <Bar dataKey="orders" fill="hsl(var(--primary))" radius={[0, 4, 4, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </CardContent>
      </Card>
    </DashboardLayout>
  );
}
