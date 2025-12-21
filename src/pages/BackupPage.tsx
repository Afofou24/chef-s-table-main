import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import { DashboardLayout } from '@/components/layout/DashboardLayout';
import { PageHeader } from '@/components/ui/page-header';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { StatusBadge } from '@/components/ui/status-badge';
import { Download, Upload, Database, Clock, CheckCircle, AlertTriangle, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface Backup {
  id: number;
  filename: string;
  size: number;
  type: 'manual' | 'automatic';
  status: 'pending' | 'completed' | 'failed';
  created_at: string;
  created_by?: number;
}

export default function BackupPage() {
  const queryClient = useQueryClient();

  // Fetch Backups
  const { data: backupsResponse, isLoading } = useQuery({
    queryKey: ['backups'],
    queryFn: async () => {
      const { data } = await api.get('backups');
      return data;
    }
  });

  const backups: Backup[] = backupsResponse?.data || [];
  const latestBackup = backups.length > 0 ? backups[0] : null;

  // Create Backup Mutation
  const createBackupMutation = useMutation({
    mutationFn: async () => {
      await api.post('backups', { notes: 'Sauvegarde manuelle' });
    },
    onSuccess: () => {
      toast.success('Sauvegarde créée avec succès');
      queryClient.invalidateQueries({ queryKey: ['backups'] });
    },
    onError: (error: any) => {
      const apiError = error.response?.data?.error;
      const message = error.response?.data?.message || 'Erreur lors de la sauvegarde';
      toast.error(apiError ? `${message}: ${apiError}` : message);
    }
  });

  // Restore Backup Mutation
  const restoreBackupMutation = useMutation({
    mutationFn: async (id: number) => {
      await api.post(`/backups/${id}/restore`);
    },
    onSuccess: () => {
      toast.success('Restauration effectuée avec succès');
    },
    onError: (error: any) => {
      const apiError = error.response?.data?.error;
      const message = error.response?.data?.message || 'Erreur lors de la restauration';
      toast.error(apiError ? `${message}: ${apiError}` : message);
    }
  });

  const handleBackup = () => {
    createBackupMutation.mutate();
  };

  const handleDownload = async (id: number, filename: string) => {
    try {
      // We use window.open or a hidden link for download retrieval
      // Since it's an authenticated API, we might need a blob approach if cookies are strict,
      // but typically standard Laravel Sanctum session allows direct link if browser has cookie.
      // Assuming SPA with token in header:
      const response = await api.get(`/backups/${id}/download`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success("Téléchargement lancé");
    } catch (e) {
      toast.error("Erreur lors du téléchargement");
    }
  };

  const handleRestore = (id: number) => {
    if (confirm('ATTENTION: Cette action va écraser toutes les données actuelles. Êtes-vous sûr ?')) {
      restoreBackupMutation.mutate(id);
    }
  };

  const formatSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  return (
    <DashboardLayout>
      <PageHeader
        title="Sauvegarde & Restauration"
        description="Gérez les sauvegardes de votre base de données"
      />

      <div className="grid gap-6 lg:grid-cols-3 mb-8">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-success/10 text-success">
                <CheckCircle className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Dernière sauvegarde</p>
                <p className="text-lg font-semibold">
                  {latestBackup ? new Date(latestBackup.created_at).toLocaleString() : 'Aucune'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Database className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Taille totale</p>
                <p className="text-lg font-semibold">
                  {formatSize(backups.reduce((acc, curr) => acc + curr.size, 0))}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-warning/10 text-warning">
                <Clock className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Prochaine sauvegarde auto</p>
                <p className="text-lg font-semibold">Demain, 02:00</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Manual Backup */}
        <Card>
          <CardHeader>
            <CardTitle className="font-display">Sauvegarde manuelle</CardTitle>
            <CardDescription>
              Créez une sauvegarde complète de votre base de données
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="p-4 rounded-lg bg-muted/50 border border-border">
              <h4 className="font-medium mb-2">Contenu de la sauvegarde</h4>
              <ul className="text-sm text-muted-foreground space-y-1">
                <li>• Utilisateurs et rôles</li>
                <li>• Menu et catégories</li>
                <li>• Commandes et paiements</li>
                <li>• Stocks et fournisseurs</li>
                <li>• Paramètres du restaurant</li>
              </ul>
            </div>
            <Button
              className="w-full"
              onClick={handleBackup}
              disabled={createBackupMutation.isPending}
            >
              {createBackupMutation.isPending ? (
                <>
                  <Loader2 className="animate-spin mr-2 h-4 w-4" />
                  Sauvegarde en cours...
                </>
              ) : (
                <>
                  <Database className="h-4 w-4 mr-2" />
                  Créer une sauvegarde
                </>
              )}
            </Button>
          </CardContent>
        </Card>

        {/* Restore */}
        <Card>
          <CardHeader>
            <CardTitle className="font-display">Restauration</CardTitle>
            <CardDescription>
              Restaurez votre base de données à partir d'une sauvegarde
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="p-4 rounded-lg bg-warning/5 border border-warning/20">
              <div className="flex gap-3">
                <AlertTriangle className="h-5 w-5 text-warning shrink-0 mt-0.5" />
                <div>
                  <h4 className="font-medium text-foreground mb-1">Attention</h4>
                  <p className="text-sm text-muted-foreground">
                    La restauration remplacera toutes les données actuelles.
                    Créez une sauvegarde avant de procéder.
                  </p>
                </div>
              </div>
            </div>
            <Button variant="outline" className="w-full" disabled>
              <Upload className="h-4 w-4 mr-2" />
              Importer une sauvegarde (Non disponible)
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* Backup History */}
      <Card className="mt-6">
        <CardHeader>
          <CardTitle className="font-display">Historique des sauvegardes</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {isLoading ? (
              <div className="flex justify-center py-4"><Loader2 className="animate-spin h-6 w-6" /></div>
            ) : backups.length === 0 ? (
              <p className="text-center text-muted-foreground py-4">Aucune sauvegarde trouvée.</p>
            ) : (
              backups.map((backup) => (
                <div
                  key={backup.id}
                  className="flex items-center justify-between p-4 rounded-lg border border-border"
                >
                  <div className="flex items-center gap-4">
                    <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${backup.status === 'completed' ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive'
                      }`}>
                      {backup.status === 'completed' ? (
                        <CheckCircle className="h-5 w-5" />
                      ) : (
                        <AlertTriangle className="h-5 w-5" />
                      )}
                    </div>
                    <div>
                      <p className="font-medium">{new Date(backup.created_at).toLocaleString()}</p>
                      <p className="text-sm text-muted-foreground">
                        {formatSize(backup.size)} • {backup.type === 'automatic' ? 'Automatique' : 'Manuelle'}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <StatusBadge status={backup.status === 'completed' ? 'success' : 'error'}>
                      {backup.status === 'completed' ? 'Réussi' : 'Échoué'}
                    </StatusBadge>
                    {backup.status === 'completed' && (
                      <>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleDownload(backup.id, backup.filename)}
                        >
                          <Download className="h-4 w-4 mr-1" />
                          Télécharger
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => handleRestore(backup.id)}
                          disabled={restoreBackupMutation.isPending}
                        >
                          {restoreBackupMutation.isPending ? <Loader2 className="h-3 w-3 animate-spin" /> : 'Restaurer'}
                        </Button>
                      </>
                    )}
                  </div>
                </div>
              )))}
          </div>
        </CardContent>
      </Card>
    </DashboardLayout>
  );
}
