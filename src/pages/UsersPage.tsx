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
import { Label } from '@/components/ui/label';
import { Plus, Search, Edit, Trash2, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

// Type definition based on API Resource
interface Role {
  id: number;
  name: string;
  code: string;
}

interface User {
  id: number;
  username: string;
  email: string;
  first_name: string;
  last_name: string;
  full_name: string;
  phone: string | null;
  is_active: boolean;
  created_at: string;
  roles: Role[];
}

export default function UsersPage() {
  const [searchTerm, setSearchTerm] = useState('');
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);

  // Form State
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    role_code: 'waiter',
    password: '',
  });

  const queryClient = useQueryClient();

  // Fetch roles
  const { data: rolesResponse } = useQuery({
    queryKey: ['roles'],
    queryFn: async () => {
      const { data } = await api.get('/roles');
      return data;
    }
  });
  const roles = rolesResponse?.data || [];

  // Fetch users
  const { data: usersResponse, isLoading, error } = useQuery({
    queryKey: ['users', searchTerm],
    queryFn: async () => {
      try {
        const params = searchTerm ? { search: searchTerm } : {};
        const { data } = await api.get('/users', { params });
        return data;
      } catch (e) {
        console.error("Users fetch error:", e);
        throw e;
      }
    },
  });

  const users = usersResponse?.data || [];

  const createMutation = useMutation({
    mutationFn: async (newUser: any) => {
      // Find the role ID
      const selectedRole = roles.find((r: Role) => r.code === newUser.role_code);
      if (!selectedRole) throw new Error("Rôle invalide sélectionné.");

      const payload = {
        username: newUser.email.split('@')[0],
        first_name: newUser.first_name,
        last_name: newUser.last_name,
        email: newUser.email,
        password: newUser.password,
        password_confirmation: newUser.password,
        phone: newUser.phone,
        roles: [selectedRole.id] // Send as array of IDs
      };
      await api.post('/users', payload);
    },
    onSuccess: () => {
      toast.success('Utilisateur créé avec succès');
      setIsDialogOpen(false);
      resetForm();
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la création');
    }
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, data }: { id: number; data: any }) => {
      // Flatten role_code to roles array if present in custom data object
      // But here we construct payload in handleSubmit
      await api.put(`/users/${id}`, data);
    },
    onSuccess: () => {
      toast.success('Utilisateur mis à jour');
      setIsDialogOpen(false);
      resetForm();
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la mise à jour');
    }
  });

  const deleteMutation = useMutation({
    mutationFn: async (userId: number) => {
      await api.delete(`/users/${userId}`);
    },
    onSuccess: () => {
      toast.success('Utilisateur supprimé');
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la suppression');
    }
  });

  const handleDelete = (userId: number) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
      deleteMutation.mutate(userId);
    }
  };

  const handleEdit = (user: User) => {
    setEditingUser(user);
    setFormData({
      first_name: user.first_name,
      last_name: user.last_name,
      email: user.email,
      phone: user.phone || '',
      role_code: user.roles?.[0]?.code || 'waiter',
      password: '',
    });
    setIsDialogOpen(true);
  };

  const handleAddNew = () => {
    setEditingUser(null);
    resetForm();
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setFormData({
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      role_code: 'waiter',
      password: '',
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const selectedRole = roles.find((r: Role) => r.code === formData.role_code);
    if (!selectedRole) {
      toast.error("Veuillez sélectionner un rôle valide.");
      return;
    }

    const payload: any = {
      first_name: formData.first_name,
      last_name: formData.last_name,
      email: formData.email,
      phone: formData.phone,
      roles: [selectedRole.id] // Backend expects 'roles' array of IDs
    };

    // Ensure username is present for updates if checking strict validation, 
    // but usually updates ignore unique check for self. 
    // User controller seems to expect 'username' on update too.
    if (editingUser) {
      payload.username = editingUser.username;
    } else {
      payload.username = formData.email.split('@')[0];
    }

    if (formData.password) {
      payload.password = formData.password;
      payload.password_confirmation = formData.password;
    }

    if (editingUser) {
      updateMutation.mutate({ id: editingUser.id, data: payload });
    } else {
      // createMutation handles payload construction internally or we pass raw form data?
      // Let's pass raw form data to createMutation wrapper logic above or just call API here.
      // To use the logic defined in createMutation mutationFn:
      createMutation.mutate(formData);
    }
  };

  const roleLabels: Record<string, string> = {
    admin: 'Administrateur',
    manager: 'Manager',
    waiter: 'Serveur',
    cook: 'Cuisinier',
    cashier: 'Caissier'
  };

  const getRoleLabel = (user: User) => {
    if (!user.roles || user.roles.length === 0) return 'Aucun rôle';
    return user.roles.map(r => roleLabels[r.code] || r.name).join(', ');
  }

  return (
    <DashboardLayout>
      <PageHeader
        title="Gestion des utilisateurs"
        description="Gérez les comptes et les permissions de votre équipe"
        actions={
          <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
            <DialogTrigger asChild>
              <Button onClick={handleAddNew}>
                <Plus className="h-4 w-4 mr-2" />
                Nouvel utilisateur
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-[500px]">
              <DialogHeader>
                <DialogTitle className="font-display">
                  {editingUser ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'}
                </DialogTitle>
              </DialogHeader>

              <form onSubmit={handleSubmit} className="space-y-4 py-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Prénom</Label>
                    <Input
                      placeholder="Jean"
                      value={formData.first_name}
                      onChange={e => setFormData({ ...formData, first_name: e.target.value })}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Nom</Label>
                    <Input
                      placeholder="Dupont"
                      value={formData.last_name}
                      onChange={e => setFormData({ ...formData, last_name: e.target.value })}
                      required
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label>Email</Label>
                  <Input
                    type="email"
                    placeholder="jean.dupont@example.com"
                    value={formData.email}
                    onChange={e => setFormData({ ...formData, email: e.target.value })}
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label>Téléphone</Label>
                  <Input
                    placeholder="06 12 34 56 78"
                    value={formData.phone}
                    onChange={e => setFormData({ ...formData, phone: e.target.value })}
                  />
                </div>

                <div className="space-y-2">
                  <Label>Rôle</Label>
                  <Select
                    value={formData.role_code}
                    onValueChange={val => setFormData({ ...formData, role_code: val })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionner un rôle" />
                    </SelectTrigger>
                    <SelectContent>
                      {/* Display dynamic roles if available, fallback to static if not yet loaded */}
                      {roles.length > 0 ? (
                        roles.map((role: Role) => (
                          <SelectItem key={role.id} value={role.code}>
                            {roleLabels[role.code] || role.name}
                          </SelectItem>
                        ))
                      ) : (
                        <>
                          <SelectItem value="admin">Administrateur</SelectItem>
                          <SelectItem value="manager">Manager</SelectItem>
                          <SelectItem value="waiter">Serveur</SelectItem>
                          <SelectItem value="cook">Cuisinier</SelectItem>
                          <SelectItem value="cashier">Caissier</SelectItem>
                        </>
                      )}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label>Mot de passe {editingUser && '(laisser vide pour ne pas changer)'}</Label>
                  <Input
                    type="password"
                    placeholder="******"
                    value={formData.password}
                    onChange={e => setFormData({ ...formData, password: e.target.value })}
                    required={!editingUser}
                  />
                </div>

                <div className="flex justify-end gap-3 pt-4">
                  <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                    Annuler
                  </Button>
                  <Button type="submit" disabled={createMutation.isPending || updateMutation.isPending}>
                    {createMutation.isPending || updateMutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                    {editingUser ? 'Enregistrer' : 'Créer'}
                  </Button>
                </div>
              </form>
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
                placeholder="Rechercher un utilisateur..."
                className="pl-10"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-primary" />
            </div>
          ) : error ? (
            <div className="text-center py-8 text-destructive">
              {(error as any)?.response?.data?.message || (error as any)?.message || 'Erreur lors du chargement des utilisateurs.'}
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nom</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Rôle</TableHead>
                  <TableHead>Date de création</TableHead>
                  <TableHead>Statut</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {users.map((user: User) => (
                  <TableRow key={user.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary font-medium text-sm">
                          {user.first_name[0]}{user.last_name[0]}
                        </div>
                        <span className="font-medium">{user.full_name}</span>
                      </div>
                    </TableCell>
                    <TableCell className="text-muted-foreground">{user.email}</TableCell>
                    <TableCell>
                      <StatusBadge status="info">{getRoleLabel(user)}</StatusBadge>
                    </TableCell>
                    <TableCell className="text-muted-foreground">
                      {new Date(user.created_at).toLocaleDateString('fr-FR')}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={user.is_active ? 'success' : 'default'}>
                        {user.is_active ? 'Actif' : 'Inactif'}
                      </StatusBadge>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleEdit(user)}
                        >
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-destructive hover:text-destructive"
                          onClick={() => handleDelete(user.id)}
                        >
                          <Trash2 className="h-4 w-4" />
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
