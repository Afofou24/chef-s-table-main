import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import { DashboardLayout } from '@/components/layout/DashboardLayout';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { StatusBadge } from '@/components/ui/status-badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Search, CreditCard, Banknote, Smartphone, Receipt, DollarSign, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface Order {
  id: number;
  order_number: string;
  table: { number: string };
  total_amount: number | string;
  waiter: { full_name: string };
  items: Array<{ id: number; quantity: number; menu_item: { name: string; price: number | string } }>;
  status: string;
}

interface Payment {
  id: number;
  order_id: number;
  order: { order_number: string };
  amount: number | string;
  payment_method: string;
  created_at: string;
}

export default function PaymentsPage() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [paymentMethod, setPaymentMethod] = useState<string>('card');
  const queryClient = useQueryClient();

  // Fetch Summary
  const { data: summary } = useQuery({
    queryKey: ['daily-summary-payments'],
    queryFn: async () => {
      const { data } = await api.get('/payments/daily-summary', { params: { last_24h: 1 } });
      return data;
    }
  });

  // Fetch Payments History
  const { data: paymentsHistoryResponse, isLoading: historyLoading } = useQuery({
    queryKey: ['payments-history', searchTerm],
    queryFn: async () => {
      const params = searchTerm ? { search: searchTerm } : {};
      const { data } = await api.get('/payments', { params });
      return data;
    }
  });

  // Fetch Ready Orders (Status 'served' usually means ready to pay)
  const { data: readyOrdersResponse, isLoading: readyLoading } = useQuery({
    queryKey: ['orders-to-pay', 'served'],
    queryFn: async () => {
      const { data } = await api.get('/orders', { params: { status: 'served' } });
      return data;
    }
  });

  const paymentsHistory: Payment[] = paymentsHistoryResponse?.data || [];
  const readyOrders: Order[] = readyOrdersResponse?.data || [];

  // Process Payment Mutation
  const processPaymentMutation = useMutation({
    mutationFn: async (payload: any) => {
      await api.post('/payments', payload);
    },
    onSuccess: () => {
      toast.success('Paiement enregistré avec succès');
      setSelectedOrder(null);
      queryClient.invalidateQueries({ queryKey: ['orders-to-pay'] });
      queryClient.invalidateQueries({ queryKey: ['payments-history'] });
      queryClient.invalidateQueries({ queryKey: ['daily-summary-payments'] });
      queryClient.invalidateQueries({ queryKey: ['tables'] }); // For table availability
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors du paiement');
    }
  });

  const handleProcessPayment = () => {
    if (!selectedOrder) return;
    processPaymentMutation.mutate({
      order_id: selectedOrder.id,
      amount: Number(selectedOrder.total_amount),
      payment_method: paymentMethod
    });
  };

  const methodLabels: Record<string, string> = {
    card: 'Carte',
    cash: 'Espèces',
    mobile: 'Mobile',
    voucher: 'Bon'
  };

  return (
    <DashboardLayout>
      <PageHeader
        title="Gestion des paiements"
        description="Encaissez les commandes et consultez l'historique"
      />

      {/* Stats */}
      <div className="grid gap-4 md:grid-cols-3 mb-8">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-success/10 text-success">
                <DollarSign className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Total (24h)</p>
                <p className="text-2xl font-bold">{Number(summary?.total_amount || 0).toFixed(2)} €</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Receipt className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Transactions</p>
                <p className="text-2xl font-bold">{summary?.total_count || 0}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-warning/10 text-warning">
                <CreditCard className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">À encaisser</p>
                <p className="text-2xl font-bold">{readyOrders.length}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Ready to Pay */}
        <Card>
          <CardHeader>
            <CardTitle className="font-display">Srvies / Prêtes à encaisser</CardTitle>
          </CardHeader>
          <CardContent>
            {readyLoading ? (
              <div className="flex justify-center p-8"><Loader2 className="h-6 w-6 animate-spin" /></div>
            ) : readyOrders.length === 0 ? (
              <p className="text-center text-muted-foreground py-8">
                Aucune commande à encaisser
              </p>
            ) : (
              <div className="space-y-3">
                {readyOrders.map((order) => (
                  <div
                    key={order.id}
                    className="flex items-center justify-between p-4 rounded-lg border border-border hover:bg-muted/50 transition-colors"
                  >
                    <div className="flex items-center gap-4">
                      <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary font-bold">
                        T{order.table?.number || '?'}
                      </div>
                      <div>
                        <p className="font-medium">#{order.order_number}</p>
                        <p className="text-sm text-muted-foreground">
                          {order.waiter?.full_name}
                        </p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-xl font-bold text-foreground mb-1">
                        {Number(order.total_amount).toFixed(2)} €
                      </p>
                      <Button size="sm" onClick={() => setSelectedOrder(order)}>
                        Encaisser
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Payment History */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle className="font-display">Historique</CardTitle>
              <div className="relative w-48">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Rechercher..."
                  className="pl-10 h-9"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
            </div>
          </CardHeader>
          <CardContent>
            {historyLoading ? (
              <div className="flex justify-center p-8"><Loader2 className="h-6 w-6 animate-spin" /></div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Commande</TableHead>
                    <TableHead>Montant</TableHead>
                    <TableHead>Mode</TableHead>
                    <TableHead>Date</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {paymentsHistory.length === 0 ? (
                    <TableRow><TableCell colSpan={4} className="text-center py-4">Aucun paiement.</TableCell></TableRow>
                  ) : (
                    paymentsHistory.map((payment) => (
                      <TableRow key={payment.id}>
                        <TableCell className="font-mono text-sm">{payment.order?.order_number || '?'}</TableCell>
                        <TableCell className="font-semibold">{Number(payment.amount).toFixed(2)} €</TableCell>
                        <TableCell>
                          <StatusBadge status="default">
                            {methodLabels[payment.payment_method] || payment.payment_method}
                          </StatusBadge>
                        </TableCell>
                        <TableCell className="text-muted-foreground">
                          {new Date(payment.created_at).toLocaleString('fr-FR', {
                            day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
                          })}
                        </TableCell>
                      </TableRow>
                    )))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Payment Dialog */}
      <Dialog open={!!selectedOrder} onOpenChange={() => setSelectedOrder(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="font-display">
              Encaisser - Table {selectedOrder?.table?.number}
            </DialogTitle>
          </DialogHeader>
          {selectedOrder && (
            <div className="space-y-6 py-4">
              <div className="border-t pt-2 flex justify-between font-bold text-lg">
                <span>Reste à payer</span>
                <span className="text-primary">{Number(selectedOrder.total_amount).toFixed(2)} €</span>
              </div>

              {/* Payment Method */}
              <div className="space-y-3">
                <Label>Mode de paiement</Label>
                <RadioGroup value={paymentMethod} onValueChange={setPaymentMethod}>
                  <div className="flex items-center space-x-2 p-3 rounded-lg border hover:bg-muted/50 cursor-pointer">
                    <RadioGroupItem value="card" id="card" />
                    <Label htmlFor="card" className="flex items-center gap-2 cursor-pointer flex-1">
                      <CreditCard className="h-4 w-4" />
                      Carte bancaire
                    </Label>
                  </div>
                  <div className="flex items-center space-x-2 p-3 rounded-lg border hover:bg-muted/50 cursor-pointer">
                    <RadioGroupItem value="cash" id="cash" />
                    <Label htmlFor="cash" className="flex items-center gap-2 cursor-pointer flex-1">
                      <Banknote className="h-4 w-4" />
                      Espèces
                    </Label>
                  </div>
                  <div className="flex items-center space-x-2 p-3 rounded-lg border hover:bg-muted/50 cursor-pointer">
                    <RadioGroupItem value="mobile" id="mobile" />
                    <Label htmlFor="mobile" className="flex items-center gap-2 cursor-pointer flex-1">
                      <Smartphone className="h-4 w-4" />
                      Paiement mobile
                    </Label>
                  </div>
                </RadioGroup>
              </div>

              <div className="flex gap-3">
                <Button variant="outline" className="flex-1" onClick={() => setSelectedOrder(null)}>
                  Annuler
                </Button>
                <Button className="flex-1" onClick={handleProcessPayment} disabled={processPaymentMutation.isPending}>
                  {processPaymentMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                  Confirmer le paiement
                </Button>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </DashboardLayout>
  );
}
