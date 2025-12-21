import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import { DashboardLayout } from '@/components/layout/DashboardLayout';
import { PageHeader } from '@/components/ui/page-header';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Building2, Bell, Shield, Palette, Loader2, Save, Key, LogOut } from 'lucide-react';
import { toast } from 'sonner';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

interface Setting {
  id: number;
  key: string;
  value: string;
  group: string;
  type: 'string' | 'boolean' | 'integer' | 'json';
  description: string;
}

export default function SettingsPage() {
  const queryClient = useQueryClient();
  const [localSettings, setLocalSettings] = useState<Record<string, string>>({});
  const [passwordForm, setPasswordForm] = useState({
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
  });
  const [isPasswordDialogOpen, setIsPasswordDialogOpen] = useState(false);

  // Fetch Grouped Settings
  const { data: groupedSettings, isLoading } = useQuery({
    queryKey: ['settings-grouped'],
    queryFn: async () => {
      const { data } = await api.get('/settings/grouped');
      return data as Record<string, Setting[]>;
    }
  });

  // Sync local state when data loads
  useEffect(() => {
    if (groupedSettings) {
      const flat: Record<string, string> = {};
      Object.values(groupedSettings).flat().forEach(s => {
        flat[s.key] = s.value;
      });
      setLocalSettings(flat);
    }
  }, [groupedSettings]);

  // Bulk Update Mutation
  const updateMutation = useMutation({
    mutationFn: async (settings: { key: string; value: string }[]) => {
      await api.put('/settings/bulk', { settings });
    },
    onSuccess: () => {
      toast.success('Paramètres enregistrés avec succès');
      queryClient.invalidateQueries({ queryKey: ['settings-grouped'] });
      queryClient.invalidateQueries({ queryKey: ['settings-global'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de l’enregistrement');
    }
  });

  // Password Change Mutation
  const changePasswordMutation = useMutation({
    mutationFn: async (data: typeof passwordForm) => {
      await api.post('/auth/change-password', data);
    },
    onSuccess: () => {
      toast.success('Mot de passe modifié avec succès');
      setIsPasswordDialogOpen(false);
      setPasswordForm({ current_password: '', new_password: '', new_password_confirmation: '' });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors du changement de mot de passe');
    }
  });

  // Revoke Sessions Mutation
  const revokeSessionsMutation = useMutation({
    mutationFn: async () => {
      await api.post('/auth/revoke-sessions');
    },
    onSuccess: () => {
      toast.success('Toutes les sessions ont été révoquées');
      // If user is also logged out, redirect could happen here
    },
    onError: (error: any) => {
      toast.error('Erreur lors de la révocation des sessions');
    }
  });

  const handleInputChange = (key: string, value: string) => {
    setLocalSettings(prev => ({ ...prev, [key]: value }));
  };

  const handleSaveGroup = (group: string) => {
    if (!groupedSettings || !groupedSettings[group]) return;
    const groupKeys = groupedSettings[group].map(s => s.key);
    const updates = groupKeys.map(key => ({
      key,
      value: localSettings[key] ?? ''
    }));
    updateMutation.mutate(updates);
  };

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
        title="Paramètres"
        description="Configurez les paramètres de votre restaurant"
      />

      <Tabs defaultValue="general" className="space-y-6">
        <TabsList>
          <TabsTrigger value="general" className="gap-2">
            <Building2 className="h-4 w-4" />
            Général
          </TabsTrigger>
          <TabsTrigger value="notifications" className="gap-2">
            <Bell className="h-4 w-4" />
            Notifications
          </TabsTrigger>
          <TabsTrigger value="security" className="gap-2">
            <Shield className="h-4 w-4" />
            Sécurité
          </TabsTrigger>
          <TabsTrigger value="appearance" className="gap-2">
            <Palette className="h-4 w-4" />
            Apparence
          </TabsTrigger>
        </TabsList>

        <TabsContent value="general">
          <Card>
            <CardHeader>
              <CardTitle>Informations du restaurant</CardTitle>
              <CardDescription>
                Configurez les informations générales de votre établissement
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label>Nom du restaurant</Label>
                  <Input
                    value={localSettings['restaurant_name'] || ''}
                    onChange={(e) => handleInputChange('restaurant_name', e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Téléphone</Label>
                  <Input
                    value={localSettings['restaurant_phone'] || ''}
                    onChange={(e) => handleInputChange('restaurant_phone', e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Email</Label>
                  <Input
                    type="email"
                    value={localSettings['restaurant_email'] || ''}
                    onChange={(e) => handleInputChange('restaurant_email', e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Adresse</Label>
                  <Input
                    value={localSettings['restaurant_address'] || ''}
                    onChange={(e) => handleInputChange('restaurant_address', e.target.value)}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Horaires d'ouverture (Texte libre)</Label>
                <Input
                  placeholder="Ex: Lun-Ven 11h-23h"
                  value={localSettings['opening_hours'] || ''}
                  onChange={(e) => handleInputChange('opening_hours', e.target.value)}
                />
              </div>

              <Button onClick={() => handleSaveGroup('general')} disabled={updateMutation.isPending}>
                {updateMutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                Enregistrer les informations
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="notifications">
          <Card>
            <CardHeader>
              <CardTitle>Préférences de notification</CardTitle>
              <CardDescription>
                Gérez les alertes et notifications du système
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Nouvelles commandes</p>
                  <p className="text-sm text-muted-foreground">
                    Recevoir une notification pour chaque nouvelle commande
                  </p>
                </div>
                <Switch
                  checked={localSettings['notify_new_order'] === 'true'}
                  onCheckedChange={(val) => handleInputChange('notify_new_order', val.toString())}
                />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Alertes de stock</p>
                  <p className="text-sm text-muted-foreground">
                    Être alerté lorsqu'un article passe sous le seuil minimum
                  </p>
                </div>
                <Switch
                  checked={localSettings['notify_low_stock'] === 'true'}
                  onCheckedChange={(val) => handleInputChange('notify_low_stock', val.toString())}
                />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Rapports quotidiens</p>
                  <p className="text-sm text-muted-foreground">
                    Recevoir un CV des ventes chaque jour
                  </p>
                </div>
                <Switch
                  checked={localSettings['notify_daily_reports'] === 'true'}
                  onCheckedChange={(val) => handleInputChange('notify_daily_reports', val.toString())}
                />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Sons de notification</p>
                  <p className="text-sm text-muted-foreground">
                    Activer les sons pour les alertes importantes
                  </p>
                </div>
                <Switch
                  checked={localSettings['notify_sounds'] === 'true'}
                  onCheckedChange={(val) => handleInputChange('notify_sounds', val.toString())}
                />
              </div>

              <Button
                className="bg-orange-500 hover:bg-orange-600 text-white"
                onClick={() => handleSaveGroup('notifications')}
                disabled={updateMutation.isPending}
              >
                {updateMutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                Enregistrer les préférences
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="security">
          <Card>
            <CardHeader>
              <CardTitle>Paramètres de sécurité</CardTitle>
              <CardDescription>
                Configurez les options de sécurité et d'authentification
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Authentification à deux facteurs</p>
                  <p className="text-sm text-muted-foreground">
                    Ajouter une couche de sécurité supplémentaire
                  </p>
                </div>
                <Switch
                  checked={localSettings['auth_two_factor'] === 'true'}
                  onCheckedChange={(val) => handleInputChange('auth_two_factor', val.toString())}
                />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Déconnexion automatique</p>
                  <p className="text-sm text-muted-foreground">
                    Se déconnecter après 30 minutes d'inactivité
                  </p>
                </div>
                <Switch
                  checked={localSettings['session_auto_logout'] === 'true'}
                  onCheckedChange={(val) => handleInputChange('session_auto_logout', val.toString())}
                />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Journalisation des actions</p>
                  <p className="text-sm text-muted-foreground">
                    Enregistrer toutes les actions des utilisateurs
                  </p>
                </div>
                <Switch
                  checked={localSettings['activity_logging'] === 'true'}
                  onCheckedChange={(val) => handleInputChange('activity_logging', val.toString())}
                />
              </div>

              <div className="pt-4 border-t flex gap-4">
                <Dialog open={isPasswordDialogOpen} onOpenChange={setIsPasswordDialogOpen}>
                  <DialogTrigger asChild>
                    <Button variant="outline">
                      <Key className="mr-2 h-4 w-4" />
                      Changer le mot de passe
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Changer le mot de passe</DialogTitle>
                      <DialogDescription>
                        Remplissez les champs ci-dessous pour modifier votre mot de passe.
                      </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                      <div className="space-y-2">
                        <Label>Mot de passe actuel</Label>
                        <Input
                          type="password"
                          value={passwordForm.current_password}
                          onChange={e => setPasswordForm(p => ({ ...p, current_password: e.target.value }))}
                        />
                      </div>
                      <div className="space-y-2">
                        <Label>Nouveau mot de passe</Label>
                        <Input
                          type="password"
                          value={passwordForm.new_password}
                          onChange={e => setPasswordForm(p => ({ ...p, new_password: e.target.value }))}
                        />
                      </div>
                      <div className="space-y-2">
                        <Label>Confirmer le nouveau mot de passe</Label>
                        <Input
                          type="password"
                          value={passwordForm.new_password_confirmation}
                          onChange={e => setPasswordForm(p => ({ ...p, new_password_confirmation: e.target.value }))}
                        />
                      </div>
                    </div>
                    <DialogFooter>
                      <Button variant="outline" onClick={() => setIsPasswordDialogOpen(false)}>Annuler</Button>
                      <Button
                        onClick={() => changePasswordMutation.mutate(passwordForm)}
                        disabled={changePasswordMutation.isPending}
                      >
                        {changePasswordMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Mettre à jour
                      </Button>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>

                <Button
                  variant="destructive"
                  onClick={() => {
                    if (confirm('Êtes-vous sûr de vouloir révoquer toutes les sessions ?')) {
                      revokeSessionsMutation.mutate();
                    }
                  }}
                  disabled={revokeSessionsMutation.isPending}
                >
                  <LogOut className="mr-2 h-4 w-4" />
                  Révoquer toutes les sessions
                </Button>
              </div>

              <Button
                className="bg-orange-500 hover:bg-orange-600 text-white"
                onClick={() => handleSaveGroup('security')}
                disabled={updateMutation.isPending}
              >
                {updateMutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                Enregistrer les préférences
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="appearance">
          <Card>
            <CardHeader>
              <CardTitle>Apparence</CardTitle>
              <CardDescription>
                Personnalisez l'interface de l'application
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Mode sombre</p>
                  <p className="text-sm text-muted-foreground">
                    Basculer entre les thèmes clair et sombre
                  </p>
                </div>
                <Switch
                  checked={localSettings['dark_mode'] === 'true'}
                  onCheckedChange={(val) => handleInputChange('dark_mode', val.toString())}
                />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Animations</p>
                  <p className="text-sm text-muted-foreground">
                    Activer les animations de l'interface
                  </p>
                </div>
                <Switch
                  checked={localSettings['animations_enabled'] === 'true'}
                  onCheckedChange={(val) => handleInputChange('animations_enabled', val.toString())}
                />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Mode compact</p>
                  <p className="text-sm text-muted-foreground">
                    Réduire l'espacement pour afficher plus d'informations
                  </p>
                </div>
                <Switch
                  checked={localSettings['compact_mode'] === 'true'}
                  onCheckedChange={(val) => handleInputChange('compact_mode', val.toString())}
                />
              </div>

              <Button
                className="bg-orange-500 hover:bg-orange-600 text-white"
                onClick={() => handleSaveGroup('appearance')}
                disabled={updateMutation.isPending}
              >
                {updateMutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                Enregistrer les préférences
              </Button>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </DashboardLayout>
  );
}
