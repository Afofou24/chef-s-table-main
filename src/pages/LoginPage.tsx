import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '@/contexts/AuthContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { UtensilsCrossed, Shield, User, CreditCard, Coffee, ChefHat } from 'lucide-react';
import { toast } from 'sonner';

// Keep icons for demo purposes presentation
const roleIcons: Record<string, React.ElementType> = {
  admin: Shield,
  manager: User,
  cashier: CreditCard,
  waiter: Coffee,
  cook: ChefHat,
};

// Updated demo accounts to match Seeded data
const demoAccounts = [
  { email: 'admin@chefstable.com', role: 'admin', label: 'Administrateur' },
  { email: 'manager@chefstable.com', role: 'manager', label: 'Manager' },
  { email: 'waiter@chefstable.com', role: 'waiter', label: 'Serveur' },
  { email: 'cook@chefstable.com', role: 'cook', label: 'Cuisinier' },
  { email: 'cashier@chefstable.com', role: 'cashier', label: 'Caissier' },
];

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      await login(email, password);
      toast.success('Connexion réussie');
      navigate('/dashboard');
    } catch (error: any) {
      console.error(error);
      const message = error.response?.data?.message || error.response?.data?.errors?.email?.[0] || 'Email ou mot de passe incorrect';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const handleQuickLogin = async (email: string) => {
    setLoading(true);
    try {
      // Default password from seeder is 'password'
      await login(email, 'password');
      toast.success('Connexion réussie');
      navigate('/dashboard');
    } catch (error: any) {
      console.error(error);
      const message = error.response?.data?.message || error.message || 'Erreur de connexion démo';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-background flex">
      {/* Left Panel - Branding */}
      <div className="hidden lg:flex lg:w-1/2 bg-sidebar items-center justify-center p-12">
        <div className="max-w-md text-center">
          <div className="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-2xl bg-primary">
            <UtensilsCrossed className="h-10 w-10 text-primary-foreground" />
          </div>
          <h1 className="font-display text-4xl font-bold text-sidebar-foreground mb-4">
            RestoManager
          </h1>
          <p className="text-lg text-sidebar-foreground/70">
            Solution professionnelle de gestion de restaurant.
            Gérez vos commandes, stocks et équipes efficacement.
          </p>
        </div>
      </div>

      {/* Right Panel - Login Form */}
      <div className="flex-1 flex items-center justify-center p-8">
        <div className="w-full max-w-md space-y-8">
          <div className="lg:hidden text-center mb-8">
            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-xl bg-primary">
              <UtensilsCrossed className="h-8 w-8 text-primary-foreground" />
            </div>
            <h1 className="font-display text-2xl font-bold">RestoManager</h1>
          </div>

          <Card className="border-border">
            <CardHeader className="space-y-1">
              <CardTitle className="font-display text-2xl">Connexion</CardTitle>
              <CardDescription>
                Entrez vos identifiants pour accéder à votre espace
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="votre@email.com"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    disabled={loading}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="password">Mot de passe</Label>
                  <Input
                    id="password"
                    type="password"
                    placeholder="••••••••"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    disabled={loading}
                  />
                </div>
                <Button type="submit" className="w-full" disabled={loading}>
                  {loading ? 'Connexion en cours...' : 'Se connecter'}
                </Button>
              </form>
            </CardContent>
          </Card>

          {/* Demo Accounts */}
          <Card className="border-border">
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Comptes de démonstration (Mdp: password)
              </CardTitle>
            </CardHeader>
            <CardContent className="grid gap-2">
              {demoAccounts.map((account) => {
                const Icon = roleIcons[account.role];
                return (
                  <button
                    key={account.email}
                    onClick={() => handleQuickLogin(account.email)}
                    disabled={loading}
                    className="flex items-center gap-3 w-full rounded-lg border border-border p-3 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground disabled:opacity-50"
                  >
                    <Icon className="h-4 w-4 text-primary" />
                    <div className="flex-1">
                      <span className="font-medium">{account.label}</span>
                      <span className="text-muted-foreground ml-2">({account.email})</span>
                    </div>
                  </button>
                );
              })}
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
