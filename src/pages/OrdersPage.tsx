import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import { DashboardLayout } from '@/components/layout/DashboardLayout';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { StatusBadge } from '@/components/ui/status-badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Search, Eye, Printer, Send, Loader2, ArrowRightCircle } from 'lucide-react';
import { toast } from 'sonner';

// --- Interfaces ---

interface Category {
  id: number;
  name: string;
}

interface MenuItem {
  id: number;
  name: string;
  price: number;
  category_id: number;
  is_available: boolean;
}

interface RestaurantTable {
  id: number;
  number: string;
  capacity: number;
  status: string;
}

interface OrderItem {
  id: number;
  menu_item_id: number;
  quantity: number;
  unit_price: number;
  status: string;
  menu_item?: {
    name: string;
  };
}

interface Order {
  id: number;
  table_id: number | null;
  table?: RestaurantTable;
  user_id: number;
  waiter?: {
    full_name: string;
  };
  status: 'pending' | 'preparing' | 'ready' | 'served' | 'paid' | 'completed' | 'cancelled';
  total_amount: number;
  created_at: string;
  items: OrderItem[];
}

const statusMap: Record<string, { label: string; status: "default" | "success" | "warning" | "info" | "error" }> = {
  pending: { label: 'En attente', status: 'warning' },
  confirmed: { label: 'Confirmé', status: 'info' },
  preparing: { label: 'En préparation', status: 'info' },
  ready: { label: 'Prêt', status: 'success' },
  served: { label: 'Servi', status: 'default' },
  completed: { label: 'Terminé', status: 'success' },
  cancelled: { label: 'Annulé', status: 'error' },
  paid: { label: 'Payé', status: 'success' },
};

export default function OrdersPage() {
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [isNewOrderOpen, setIsNewOrderOpen] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  // New Order Form State
  const [newOrderTableId, setNewOrderTableId] = useState<string>('');
  const [newOrderItems, setNewOrderItems] = useState<{ menuItemId: number; quantity: number }[]>([]);

  const queryClient = useQueryClient();

  // --- Queries ---

  const { data: ordersResponse, isLoading: isLoadingOrders } = useQuery({
    queryKey: ['orders', statusFilter],
    queryFn: async () => {
      const params: any = {};
      if (statusFilter !== 'all') params.status = statusFilter;
      const { data } = await api.get('orders', { params });
      return data;
    },
  });

  const orders: Order[] = ordersResponse?.data || [];

  const { data: tablesResponse } = useQuery({
    queryKey: ['tables'],
    queryFn: async () => {
      const { data } = await api.get('tables');
      return data;
    },
    enabled: isNewOrderOpen, // Only fetch when dialog opens
  });
  const tables: RestaurantTable[] = tablesResponse?.data || [];

  const { data: categoriesResponse } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => {
      const { data } = await api.get('categories');
      return data;
    },
    enabled: isNewOrderOpen,
  });
  const categories: Category[] = categoriesResponse?.data || [];

  const { data: menuItemsResponse } = useQuery({
    queryKey: ['menu-items'],
    queryFn: async () => {
      const { data } = await api.get('menu-items', { params: { is_available: 1 } });
      return data;
    },
    enabled: isNewOrderOpen,
  });
  const menuItems: MenuItem[] = menuItemsResponse?.data || [];

  // --- Mutations ---

  const createOrderMutation = useMutation({
    mutationFn: async (data: any) => {
      await api.post('orders', data);
    },
    onSuccess: () => {
      toast.success('Commande créée avec succès');
      setIsNewOrderOpen(false);
      setNewOrderTableId('');
      setNewOrderItems([]);
      queryClient.invalidateQueries({ queryKey: ['orders'] });
      queryClient.invalidateQueries({ queryKey: ['tables'] }); // Update table status
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "Erreur lors de la création");
    }
  });

  const updateStatusMutation = useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) => {
      await api.patch(`/orders/${id}/status`, { status });
    },
    onSuccess: () => {
      toast.success("Statut mis à jour");
      queryClient.invalidateQueries({ queryKey: ['orders'] });
      setSelectedOrder(null);
    }
  });

  // --- Event Handlers ---

  const handleCreateOrder = () => {
    if (!newOrderTableId) {
      toast.error("Veuillez sélectionner une table");
      return;
    }
    if (newOrderItems.length === 0) {
      toast.error("Veuillez ajouter au moins un article");
      return;
    }

    const payload = {
      table_id: parseInt(newOrderTableId),
      order_type: 'dine_in',
      items: newOrderItems.map(item => ({
        menu_item_id: item.menuItemId,
        quantity: item.quantity
      }))
    };

    createOrderMutation.mutate(payload);
  };

  const handleSendToKitchen = (orderId: number) => {
    updateStatusMutation.mutate({ id: orderId, status: 'preparing' });
  };

  const handleMarkServed = (orderId: number) => {
    updateStatusMutation.mutate({ id: orderId, status: 'served' });
    toast.success("Commande marquée comme servie");
  };

  const toggleMenuItem = (itemId: number) => {
    setNewOrderItems(prev => {
      const existing = prev.find(i => i.menuItemId === itemId);
      if (existing) {
        return prev.filter(i => i.menuItemId !== itemId);
      } else {
        return [...prev, { menuItemId: itemId, quantity: 1 }];
      }
    });
  };

  const updateQuantity = (itemId: number, delta: number) => {
    setNewOrderItems(prev => prev.map(item => {
      if (item.menuItemId === itemId) {
        const newQty = Math.max(1, item.quantity + delta);
        return { ...item, quantity: newQty };
      }
      return item;
    }));
  };

  const getQuantity = (itemId: number) => newOrderItems.find(i => i.menuItemId === itemId)?.quantity || 0;


  // Client-side filtering for search term (since API doesn't support generic string search yet)
  const filteredOrders = orders.filter(order => {
    const tableMatch = order.table?.number?.toString().includes(searchTerm) || false;
    const serverMatch = order.server?.full_name?.toLowerCase().includes(searchTerm.toLowerCase()) || false;
    // status is already filtered by API if not 'all', but if 'all' we don't filter client side
    return searchTerm === '' || tableMatch || serverMatch;
  });

  return (
    <DashboardLayout>
      <PageHeader
        title="Gestion des commandes"
        description="Suivez et gérez les commandes en cours"
        actions={
          <Dialog open={isNewOrderOpen} onOpenChange={setIsNewOrderOpen}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="h-4 w-4 mr-2" />
                Nouvelle commande
              </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle className="font-display">Nouvelle commande</DialogTitle>
              </DialogHeader>
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Table</label>
                  <Select value={newOrderTableId} onValueChange={setNewOrderTableId}>
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionner une table" />
                    </SelectTrigger>
                    <SelectContent>
                      {tables.filter(t => t.status === 'available').map((table) => (
                        <SelectItem key={table.id} value={table.id.toString()}>
                          Table {table.number} ({table.capacity} places)
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">Ajouter des plats</label>
                  <div className="max-h-64 overflow-y-auto space-y-2 border rounded-lg p-3">
                    {categories.map((category) => (
                      <div key={category.id}>
                        <p className="text-xs font-semibold text-muted-foreground uppercase mb-2 mt-2">
                          {category.name}
                        </p>
                        <div className="space-y-1">
                          {menuItems
                            .filter(item => item.category_id === category.id)
                            .map((item) => {
                              const qty = getQuantity(item.id);
                              return (
                                <div
                                  key={item.id}
                                  className={`flex items-center justify-between p-2 rounded cursor-pointer ${qty > 0 ? 'bg-primary/5 border border-primary' : 'hover:bg-muted'}`}
                                  onClick={() => toggleMenuItem(item.id)}
                                >
                                  <span className="text-sm font-medium">{item.name}</span>
                                  <div className="flex items-center gap-3">
                                    <span className="text-sm">{Number(item.price).toFixed(2)} €</span>
                                    {qty > 0 && (
                                      <div className="flex items-center gap-2" onClick={e => e.stopPropagation()}>
                                        <Button size="icon" variant="outline" className="h-6 w-6" onClick={() => updateQuantity(item.id, -1)}>-</Button>
                                        <span className="text-sm w-4 text-center">{qty}</span>
                                        <Button size="icon" variant="outline" className="h-6 w-6" onClick={() => updateQuantity(item.id, 1)}>+</Button>
                                      </div>
                                    )}
                                  </div>
                                </div>
                              );
                            })}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="flex justify-end gap-3 pt-4">
                  <Button variant="outline" onClick={() => setIsNewOrderOpen(false)}>
                    Annuler
                  </Button>
                  <Button onClick={handleCreateOrder} disabled={createOrderMutation.isPending}>
                    {createOrderMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                    Créer la commande
                  </Button>
                </div>
              </div>
            </DialogContent>
          </Dialog>
        }
      />

      <Card>
        <CardHeader>
          <div className="flex items-center gap-4">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Rechercher par table ou serveur..."
                className="pl-10"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-48">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tous les statuts</SelectItem>
                <SelectItem value="pending">En attente</SelectItem>
                <SelectItem value="preparing">En préparation</SelectItem>
                <SelectItem value="ready">Prêt</SelectItem>
                <SelectItem value="served">Servi</SelectItem>
                <SelectItem value="paid">Payé</SelectItem>
                <SelectItem value="completed">Terminé</SelectItem>
                <SelectItem value="cancelled">Annulé</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardHeader>
        <CardContent>
          {isLoadingOrders ? (
            <div className="flex justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-primary" />
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Commande</TableHead>
                  <TableHead>Table</TableHead>
                  <TableHead>Articles</TableHead>
                  <TableHead>Serveur</TableHead>
                  <TableHead>Total</TableHead>
                  <TableHead>Statut</TableHead>
                  <TableHead>Heure</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredOrders.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                      Aucune commande trouvée
                    </TableCell>
                  </TableRow>
                ) : filteredOrders.map((order) => (
                  <TableRow key={order.id}>
                    <TableCell className="font-mono text-sm">#{order.id}</TableCell>
                    <TableCell>
                      <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary font-semibold">
                        {order.table ? `T${order.table.number}` : 'Emporter'}
                      </div>
                    </TableCell>
                    <TableCell>{order.items?.length || 0} articles</TableCell>
                    <TableCell className="text-muted-foreground">{order.waiter?.full_name || 'Inconnu'}</TableCell>
                    <TableCell className="font-semibold">{Number(order.total_amount).toFixed(2)} €</TableCell>
                    <TableCell>
                      <StatusBadge status={statusMap[order.status]?.status || 'default'}>
                        {statusMap[order.status]?.label || order.status}
                      </StatusBadge>
                    </TableCell>
                    <TableCell className="text-muted-foreground">
                      {new Date(order.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-1">
                        <Dialog>
                          <DialogTrigger asChild>
                            <Button variant="ghost" size="icon" onClick={() => setSelectedOrder(order)}>
                              <Eye className="h-4 w-4" />
                            </Button>
                          </DialogTrigger>
                          <DialogContent>
                            <DialogHeader>
                              <DialogTitle className="font-display">
                                Commande #{order.id} - {order.table ? `Table ${order.table.number}` : 'A Emporter'}
                              </DialogTitle>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                              {order.items?.map((item) => (
                                <div key={item.id} className="flex justify-between items-center p-3 rounded-lg bg-muted/50">
                                  <div>
                                    <p className="font-medium">{item.menu_item?.name || 'Article supprimé'}</p>
                                    <p className="text-sm text-muted-foreground">
                                      Quantité: {item.quantity}
                                    </p>
                                  </div>
                                  <div className="text-right">
                                    <p className="font-semibold">{(Number(item.unit_price) * item.quantity).toFixed(2)} €</p>
                                    <StatusBadge status={statusMap[item.status]?.status || 'default'}>
                                      {statusMap[item.status]?.label || item.status}
                                    </StatusBadge>
                                  </div>
                                </div>
                              ))}
                              <div className="border-t pt-4 flex justify-between items-center">
                                <span className="text-lg font-semibold">Total</span>
                                <span className="text-xl font-bold text-primary">{Number(order.total_amount).toFixed(2)} €</span>
                              </div>
                            </div>
                          </DialogContent>
                        </Dialog>
                        {order.status === 'pending' && (
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleSendToKitchen(order.id)}
                            title="Envoyer en cuisine"
                          >
                            <Send className="h-4 w-4" />
                          </Button>
                        )}
                        {order.status === 'ready' && (
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleMarkServed(order.id)}
                            title="Envoyer à l'encaissement"
                            className="text-success hover:text-success/80"
                          >
                            <ArrowRightCircle className="h-4 w-4" />
                          </Button>
                        )}
                        <Button variant="ghost" size="icon">
                          <Printer className="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </DashboardLayout>
  );
}
