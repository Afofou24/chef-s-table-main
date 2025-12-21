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
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Plus, Search, Edit, Package, AlertTriangle, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface StockItem {
  id: number;
  name: string;
  sku: string;
  quantity: number;
  unit: string;
  min_quantity: number;
  supplier: string | null;
  updated_at: string;
}

export default function InventoryPage() {
  const [searchTerm, setSearchTerm] = useState('');
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    sku: '',
    quantity: '0',
    unit: 'kg',
    min_quantity: '5',
    supplier: ''
  });

  const queryClient = useQueryClient();

  // Fetch Stock Items
  const { data: stockResponse, isLoading } = useQuery({
    queryKey: ['stock', searchTerm],
    queryFn: async () => {
      const params = searchTerm ? { search: searchTerm } : {};
      const { data } = await api.get('stock', { params });
      return data;
    }
  });

  const stockItems: StockItem[] = stockResponse?.data || [];
  const lowStockCount = stockItems.filter(item => item.quantity <= item.min_quantity).length;

  // Create Stock Mutation
  const createMutation = useMutation({
    mutationFn: async (data: any) => {
      await api.post('stock', data);
    },
    onSuccess: () => {
      toast.success('Article ajouté au stock');
      setIsDialogOpen(false);
      resetForm();
      queryClient.invalidateQueries({ queryKey: ['stock'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de l\'ajout');
    }
  });

  const resetForm = () => {
    setFormData({
      name: '',
      sku: '',
      quantity: '0',
      unit: 'kg',
      min_quantity: '5',
      supplier: ''
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const payload = {
      ...formData,
      quantity: parseFloat(formData.quantity),
      min_quantity: parseFloat(formData.min_quantity),
      sku: formData.sku || `SKU-${Date.now()}`
    };
    createMutation.mutate(payload);
  };

  return (
    <DashboardLayout>
      <PageHeader
        title="Gestion des stocks"
        description="Suivez et gérez l'inventaire du restaurant"
        actions={
          <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
            <DialogTrigger asChild>
              <Button onClick={resetForm}>
                <Plus className="h-4 w-4 mr-2" />
                Nouvel article
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle className="font-display">Nouvel article en stock</DialogTitle>
              </DialogHeader>
              <form onSubmit={handleSubmit} className="space-y-4 py-4">
                <div className="space-y-2">
                  <Label>Nom de l'article</Label>
                  <Input
                    placeholder="Ex: Bœuf Angus"
                    value={formData.name}
                    onChange={e => setFormData({ ...formData, name: e.target.value })}
                    required
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Quantité Initiale</Label>
                    <Input
                      type="number"
                      step="0.01"
                      value={formData.quantity}
                      onChange={e => setFormData({ ...formData, quantity: e.target.value })}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Unité</Label>
                    <Input
                      placeholder="kg, L, pièces..."
                      value={formData.unit}
                      onChange={e => setFormData({ ...formData, unit: e.target.value })}
                      required
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Quantité minimum (Alerte)</Label>
                  <Input
                    type="number"
                    step="0.01"
                    value={formData.min_quantity}
                    onChange={e => setFormData({ ...formData, min_quantity: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label>Fournisseur</Label>
                  <Input
                    placeholder="Nom du fournisseur"
                    value={formData.supplier}
                    onChange={e => setFormData({ ...formData, supplier: e.target.value })}
                  />
                </div>
                <div className="flex justify-end gap-3 pt-4">
                  <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                    Annuler
                  </Button>
                  <Button type="submit" disabled={createMutation.isPending}>
                    {createMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                    Créer
                  </Button>
                </div>
              </form>
            </DialogContent>
          </Dialog>
        }
      />

      {/* Alert Banner */}
      {lowStockCount > 0 && (
        <Card className="mb-6 border-warning/50 bg-warning/5">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <AlertTriangle className="h-5 w-5 text-warning" />
              <div>
                <p className="font-medium text-foreground">
                  {lowStockCount} article{lowStockCount > 1 ? 's' : ''} en stock bas
                </p>
                <p className="text-sm text-muted-foreground">
                  Pensez à réapprovisionner ces articles
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <div className="flex items-center gap-4">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Rechercher un article..."
                className="pl-10"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex justify-center py-8"><Loader2 className="h-8 w-8 animate-spin text-primary" /></div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Article</TableHead>
                  <TableHead>Quantité</TableHead>
                  <TableHead>Niveau</TableHead>
                  <TableHead>Fournisseur</TableHead>
                  <TableHead>Dernière mise à jour</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {stockItems.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                      Aucun article trouvé.
                    </TableCell>
                  </TableRow>
                ) : (
                  stockItems.map((item) => {
                    const stockPercentage = Math.min((item.quantity / (item.min_quantity * 2)) * 100, 100);
                    const isLow = item.quantity <= item.min_quantity;

                    return (
                      <TableRow key={item.id}>
                        <TableCell>
                          <div className="flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                              <Package className="h-4 w-4 text-primary" />
                            </div>
                            <span className="font-medium">{item.name}</span>
                          </div>
                        </TableCell>
                        <TableCell>
                          <span className={isLow ? 'text-destructive font-semibold' : ''}>
                            {item.quantity} {item.unit}
                          </span>
                          <span className="text-muted-foreground text-sm ml-2">
                            (min: {item.min_quantity})
                          </span>
                        </TableCell>
                        <TableCell className="w-32">
                          <Progress
                            value={stockPercentage}
                            className={isLow ? '[&>div]:bg-destructive' : '[&>div]:bg-success'}
                          />
                        </TableCell>
                        <TableCell className="text-muted-foreground">{item.supplier || '-'}</TableCell>
                        <TableCell className="text-muted-foreground">
                          {new Date(item.updated_at).toLocaleDateString('fr-FR')}
                        </TableCell>
                        <TableCell className="text-right">
                          <div className="flex justify-end gap-2">
                            {isLow && (
                              <StatusBadge status="error">Stock bas</StatusBadge>
                            )}
                            <Button variant="ghost" size="icon">
                              <Edit className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    );
                  }))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </DashboardLayout>
  );
}
