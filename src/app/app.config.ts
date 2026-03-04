import { ApplicationConfig, provideZoneChangeDetection, importProvidersFrom } from '@angular/core';
import { provideRouter } from '@angular/router';
import { routes } from './app.routes';
import { provideHttpClient, withFetch } from '@angular/common/http';
import { LucideAngularModule, Heart, Menu, X, Phone, Mail, Facebook, Instagram, Youtube, History, Users, User, Target, Send, CheckCircle, ShieldCheck, Download, LayoutDashboard, Image, MessageSquare, LogOut, Lock, Eye, EyeOff, BookOpen, ArrowRight, Calendar, GraduationCap, Briefcase } from 'lucide-angular';

export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),
    provideRouter(routes),
    provideHttpClient(withFetch()),
    importProvidersFrom(
      LucideAngularModule.pick({
        Heart, Menu, X, Phone, Mail, Facebook, Instagram, Youtube,
        History, Users, Target, Send, CheckCircle, ShieldCheck, Download,
        LayoutDashboard, Image, MessageSquare, LogOut, Lock, User, Eye, EyeOff,
        BookOpen, ArrowRight, Calendar, GraduationCap, Briefcase
      })
    )
  ]
};
