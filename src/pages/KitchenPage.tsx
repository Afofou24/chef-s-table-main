import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import { DashboardLayout } from '@/components/layout/DashboardLayout';
import { PageHeader } from '@/components/ui/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { StatusBadge } from '@/components/ui/status-badge';
import { Clock, ChefHat, Check, Timer, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

interface OrderItem {
  id: number;
  order_id: number;
  order?: {
    order_number: string;
    table?: { number: string | number };
    waiter?: { first_name: string }
  };
  menu_item: { name: string };
  quantity: number;
  notes: string | null;
  status: 'pending' | 'preparing' | 'ready' | 'served' | 'cancelled';
  created_at: string;
}

export default function KitchenPage() {
  const queryClient = useQueryClient();

  // Fetch kitchen items
  const { data: kitchenItemsResponse, isLoading, isError } = useQuery({
    queryKey: ['kitchen-items'],
    queryFn: async () => {
      const { data } = await api.get('/kitchen/items');
      return data;
    },
    refetchInterval: 10000,
  });

  // Backend can return an object { "order_id": [items...] } or an array
  // We normalize it to a flat array of items first
  const normalizedItems: OrderItem[] = kitchenItemsResponse
    ? (Array.isArray(kitchenItemsResponse)
      ? kitchenItemsResponse
      : Object.values(kitchenItemsResponse).flat()) as OrderItem[]
    : [];

  // Re-group items by Order for display (to handle sorting by order time)
  const ordersMap: Record<number, {
    id: number;
    order_number: string;
    table_number: string;
    waiter_name: string;
    created_at: string;
    items: OrderItem[]
  }> = {};

  normalizedItems.forEach(item => {
    if (!item) return;
    if (!ordersMap[item.order_id]) {
      ordersMap[item.order_id] = {
        id: item.order_id,
        order_number: item.order?.order_number || `ORD-${item.order_id}`,
        table_number: item.order?.table?.number?.toString() || '?',
        waiter_name: item.order?.waiter?.first_name || 'Personnel',
        created_at: item.created_at,
        items: []
      };
    }
    ordersMap[item.order_id].items.push(item);
  });

  const ordersList = Object.values(ordersMap).sort((a, b) =>
    new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
  );

  const updateStatusMutation = useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) => {
      await api.patch(`/order-items/${id}/status`, { status });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['kitchen-items'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur de mise à jour');
    }
  });

  const handleUpdateStatus = (itemId: number, status: string) => {
    updateStatusMutation.mutate({ id: itemId, status });
    const label = status === 'preparing' ? 'Préparation commencée' : 'Plat prêt';
    toast.success(label);
  };

  const getTimeElapsed = (createdAt: string) => {
    try {
      const diff = Date.now() - new Date(createdAt).getTime();
      return Math.floor(diff / 60000);
    } catch {
      return 0;
    }
  };

  return (
    <DashboardLayout>
      <PageHeader
        title="Écran Cuisine"
        description="Commandes en attente de préparation (Auto-refresh 10s)"
      />

      {isLoading ? (
        <div className="flex justify-center py-12"><Loader2 className="h-8 w-8 animate-spin text-primary" /></div>
      ) : isError ? (
        <div className="text-center py-12 text-destructive">
          Une erreur est survenue lors du chargement des commandes.
        </div>
      ) : ordersList.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center">
            <ChefHat className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
            <h3 className="text-lg font-semibold mb-2">Aucune commande en attente</h3>
            <p className="text-muted-foreground">Les nouvelles commandes apparaîtront ici</p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {ordersList.map((order) => {
            const timeElapsed = getTimeElapsed(order.created_at);
            const isUrgent = timeElapsed > 15;

            return (
              <Card
                key={order.id}
                className={cn(
                  'border-2 h-fit flex flex-col',
                  isUrgent ? 'border-destructive/50 bg-destructive/5' : 'border-border'
                )}
              >
                <CardHeader className="pb-3">
                  <div className="flex items-center justify-between">
                    <CardTitle className="font-display text-xl">
                      Table {order.table_number}
                    </CardTitle>
                    <div className={cn(
                      'flex items-center gap-1 text-sm font-medium px-2 py-1 rounded-full',
                      isUrgent
                        ? 'bg-destructive/10 text-destructive'
                        : 'bg-muted text-muted-foreground'
                    )}>
                      <Timer className="h-3.5 w-3.5" />
                      {timeElapsed} min
                    </div>
                  </div>
                  <p className="text-sm text-muted-foreground">
                    #{order.order_number} • {order.waiter_name}
                  </p>
                </CardHeader>
                <CardContent className="space-y-3 flex-1">
                  {order.items.map((item) => (
                    <div
                      key={item.id}
                      className={cn(
                        'p-3 rounded-lg border',
                        item.status === 'ready'
                          ? 'bg-success/10 border-success/30'
                          : item.status === 'preparing'
                            ? 'bg-primary/10 border-primary/30'
                            : 'bg-muted/50 border-border'
                      )}
                    >
                      <div className="flex items-start justify-between mb-2">
                        <div className="flex-1 pr-2">
                          <p className="font-semibold">{item.menu_item?.name || 'Article inconnu'}</p>
                          <p className="text-sm text-muted-foreground">
                            Quantité: {item.quantity}
                          </p>
                          {item.notes && (
                            <p className="text-sm text-warning mt-1 italic">Note: {item.notes}</p>
                          )}
                        </div>
                        <StatusBadge
                          status={
                            item.status === 'ready' ? 'success' :
                              item.status === 'preparing' ? 'info' : 'warning'
                          }
                        >
                          {item.status === 'ready' ? 'Prêt' :
                            item.status === 'preparing' ? 'En cours' : 'En attente'}
                        </StatusBadge>
                      </div>

                      {item.status === 'pending' && (
                        <Button
                          size="sm"
                          className="w-full"
                          onClick={() => handleUpdateStatus(item.id, 'preparing')}
                          disabled={updateStatusMutation.isPending}
                        >
                          <Clock className="h-3.5 w-3.5 mr-1" />
                          Commencer
                        </Button>
                      )}

                      {item.status === 'preparing' && (
                        <Button
                          size="sm"
                          className="w-full bg-success hover:bg-success/90"
                          onClick={() => handleUpdateStatus(item.id, 'ready')}
                          disabled={updateStatusMutation.isPending}
                        >
                          <Check className="h-3.5 w-3.5 mr-1" />
                          Marquer prêt
                        </Button>
                      )}

                      {item.status === 'ready' && (
                        <div className="text-center text-sm text-success font-medium">
                          ✓ Prêt à servir
                        </div>
                      )}
                    </div>
                  ))}
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}
    </DashboardLayout>
  );
}
