import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import api from '@/lib/api';
import { useQuery } from '@tanstack/react-query';

interface SettingsContextType {
    settings: Record<string, string>;
    isLoading: boolean;
    refreshSettings: () => void;
}

const SettingsContext = createContext<SettingsContextType | undefined>(undefined);

export function SettingsProvider({ children }: { children: ReactNode }) {
    const { data: groupedSettings, isLoading, refetch } = useQuery({
        queryKey: ['settings-global'],
        queryFn: async () => {
            const { data } = await api.get('/settings/grouped');
            return data as Record<string, any[]>;
        },
        staleTime: 1000 * 60 * 5, // 5 minutes
    });

    const [settings, setSettings] = useState<Record<string, string>>({});

    useEffect(() => {
        if (groupedSettings) {
            const flat: Record<string, string> = {};
            Object.values(groupedSettings).flat().forEach(s => {
                flat[s.key] = s.value;
            });
            setSettings(flat);
        }
    }, [groupedSettings]);

    // Apply visual settings
    useEffect(() => {
        const root = window.document.documentElement;

        // Dark Mode
        if (settings['dark_mode'] === 'true') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }

        // Compact Mode
        if (settings['compact_mode'] === 'true') {
            root.classList.add('compact-mode');
        } else {
            root.classList.remove('compact-mode');
        }

        // Animations
        if (settings['animations_enabled'] === 'false') {
            root.classList.add('animations-disabled');
        } else {
            root.classList.remove('animations-disabled');
        }
    }, [settings]);

    return (
        <SettingsContext.Provider value={{ settings, isLoading, refreshSettings: refetch }}>
            {children}
        </SettingsContext.Provider>
    );
}

export function useSettings() {
    const context = useContext(SettingsContext);
    if (context === undefined) {
        throw new Error('useSettings must be used within a SettingsProvider');
    }
    return context;
}
