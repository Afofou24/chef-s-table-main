import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import { DashboardLayout } from '@/components/layout/DashboardLayout';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Users, Utensils, Clock, Sparkles, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

interface Table {
  id: number;
  number: string;
  capacity: number;
  status: 'available' | 'occupied' | 'reserved' | 'unavailable';
  location: string | null;
  current_order?: {
    id: number;
    total_amount: string | number;
    items_count: number;
    created_at: string;
  };
}

const statusColors: Record<string, string> = {
  available: 'bg-success/10 border-success/30 text-success',
  occupied: 'bg-primary/10 border-primary/30 text-primary',
  reserved: 'bg-warning/10 border-warning/30 text-warning',
  unavailable: 'bg-muted border-border text-muted-foreground',
};

const statusLabels: Record<string, string> = {
  available: 'Disponible',
  occupied: 'Occupée',
  reserved: 'Réservée',
  unavailable: 'Nettoyage / Inisp.',
};

export default function TablesPage() {
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [newTable, setNewTable] = useState({ number: '', capacity: '4', location: 'interieur' });
  const queryClient = useQueryClient();

  // Fetch Tables
  const { data: tablesResponse, isLoading } = useQuery({
    queryKey: ['tables'],
    queryFn: async () => {
      const { data } = await api.get('tables');
      return data;
    }
  });

  const tables: Table[] = tablesResponse?.data || [];

  // Create Table Mutation
  const createTableMutation = useMutation({
    mutationFn: async (data: any) => {
      await api.post('tables', data);
    },
    onSuccess: () => {
      toast.success('Table créée avec succès');
      setIsDialogOpen(false);
      setNewTable({ number: '', capacity: '4', location: 'interieur' });
      queryClient.invalidateQueries({ queryKey: ['tables'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la création');
    }
  });

  // Update Status Mutation
  const updateStatusMutation = useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) => {
      await api.patch(`/tables/${id}/status`, { status });
    },
    onSuccess: () => {
      toast.success('Statut mis à jour');
      queryClient.invalidateQueries({ queryKey: ['tables'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la mise à jour');
    }
  });

  const handleStatusChange = (tableId: number, newStatus: string) => {
    updateStatusMutation.mutate({ id: tableId, status: newStatus });
  };

  const handleCreateTable = (e: React.FormEvent) => {
    e.preventDefault();
    createTableMutation.mutate({
      number: newTable.number,
      capacity: parseInt(newTable.capacity),
      location: newTable.location
    });
  };

  return (
    <DashboardLayout>
      <PageHeader
        title="Gestion des tables"
        description="Vue d'ensemble et gestion des tables du restaurant"
        actions={
          <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="h-4 w-4 mr-2" />
                Nouvelle table
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle className="font-display">Nouvelle table</DialogTitle>
              </DialogHeader>
              <form onSubmit={handleCreateTable} className="space-y-4 py-4">
                <div className="space-y-2">
                  <Label>Numéro de table</Label>
                  <Input
                    type="text"
                    placeholder="11"
                    value={newTable.number}
                    onChange={e => setNewTable({ ...newTable, number: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label>Capacité (personnes)</Label>
                  <Input
                    type="number"
                    placeholder="4"
                    value={newTable.capacity}
                    onChange={e => setNewTable({ ...newTable, capacity: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label>Emplacement</Label>
                  <Select
                    value={newTable.location}
                    onValueChange={val => setNewTable({ ...newTable, location: val })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Choisir un emplacement" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="interieur">Intérieur</SelectItem>
                      <SelectItem value="terrasse">Terrasse</SelectItem>
                      <SelectItem value="prive">Privé</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="flex justify-end gap-3 pt-4">
                  <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                    Annuler
                  </Button>
                  <Button type="submit" disabled={createTableMutation.isPending}>
                    {createTableMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                    Créer
                  </Button>
                </div>
              </form>
            </DialogContent>
          </Dialog>
        }
      />

      {/* Legend */}
      <div className="flex flex-wrap gap-4 mb-6">
        {(Object.keys(statusLabels) as Array<keyof typeof statusLabels>).map((status) => (
          <div key={status} className="flex items-center gap-2">
            <div className={cn('h-3 w-3 rounded-full', statusColors[status].split(' ')[0])} />
            <span className="text-sm text-muted-foreground">{statusLabels[status]}</span>
          </div>
        ))}
      </div>

      {/* Tables Grid */}
      {isLoading ? (
        <div className="flex justify-center py-12"><Loader2 className="h-8 w-8 animate-spin text-primary" /></div>
      ) : tables.length === 0 ? (
        <div className="text-center py-12 border-2 border-dashed rounded-lg text-muted-foreground">
          Aucune table configurée.
        </div>
      ) : (
        <div className="grid gap-4 grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
          {tables.map((table) => {
            return (
              <Card
                key={table.id}
                className={cn(
                  'cursor-pointer transition-all hover:shadow-md border-2',
                  statusColors[table.status]
                )}
              >
                <CardContent className="p-4">
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-2xl font-bold">T{table.number}</h3>
                    <div className="flex items-center text-sm">
                      <Users className="h-4 w-4 mr-1" />
                      {table.capacity}
                    </div>
                  </div>

                  <p className="text-sm font-medium mb-3">{statusLabels[table.status]}</p>

                  {table.status === 'occupied' && table.current_order && (
                    <div className="space-y-2 pt-2 border-t border-current/20">
                      <div className="flex items-center justify-between text-xs">
                        <span className="flex items-center">
                          <Utensils className="h-3 w-3 mr-1" />
                          {table.current_order.items_count || 0} articles
                        </span>
                        <span className="font-semibold">{Number(table.current_order.total_amount).toFixed(2)} €</span>
                      </div>
                      <div className="flex items-center text-xs opacity-70">
                        <Clock className="h-3 w-3 mr-1" />
                        {new Date(table.current_order.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                      </div>
                    </div>
                  )}

                  {table.status === 'available' && (
                    <Button
                      size="sm"
                      className="w-full mt-2"
                      variant="outline"
                    >
                      <Utensils className="h-3.5 w-3.5 mr-1" />
                      Nouvelle commande
                    </Button>
                  )}

                  {table.status === 'unavailable' && (
                    <Button
                      size="sm"
                      className="w-full mt-2"
                      variant="outline"
                      onClick={() => handleStatusChange(table.id, 'available')}
                      disabled={updateStatusMutation.isPending}
                    >
                      <Sparkles className="h-3.5 w-3.5 mr-1" />
                      Marquer propre
                    </Button>
                  )}
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}

      {/* Quick Stats */}
      {!isLoading && (
        <div className="mt-8 grid gap-4 md:grid-cols-4">
          <Card>
            <CardContent className="p-4 text-center">
              <p className="text-3xl font-bold text-success">
                {tables.filter(t => t.status === 'available').length}
              </p>
              <p className="text-sm text-muted-foreground">Disponibles</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4 text-center">
              <p className="text-3xl font-bold text-primary">
                {tables.filter(t => t.status === 'occupied').length}
              </p>
              <p className="text-sm text-muted-foreground">Occupées</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4 text-center">
              <p className="text-3xl font-bold text-warning">
                {tables.filter(t => t.status === 'reserved').length}
              </p>
              <p className="text-sm text-muted-foreground">Réservées</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4 text-center">
              <p className="text-3xl font-bold text-muted-foreground">
                {tables.filter(t => t.status === 'unavailable').length}
              </p>
              <p className="text-sm text-muted-foreground">En nettoyage</p>
            </CardContent>
          </Card>
        </div>
      )}
    </DashboardLayout>
  );
}
