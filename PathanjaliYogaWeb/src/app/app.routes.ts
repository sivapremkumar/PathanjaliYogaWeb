import { Routes } from '@angular/router';
import { HomeComponent } from './pages/home/home.component';
import { AboutComponent } from './pages/about/about.component';
import { ProgramsListComponent } from './pages/programs/programs.component';
import { GetInvolvedComponent } from './pages/get-involved/get-involved.component';
import { NewsComponent } from './pages/news/news.component';
import { GalleryComponent } from './pages/gallery/gallery.component';
import { ContactComponent } from './pages/contact/contact.component';
import { DonateComponent } from './pages/donate/donate.component';
import { LoginComponent } from './admin/login/login.component';
import { DashboardComponent } from './admin/dashboard/dashboard.component';
import { authGuard } from './services/auth.guard';

export const routes: Routes = [
    { path: '', component: HomeComponent },
    { path: 'about', component: AboutComponent },
    { path: 'programs', component: ProgramsListComponent },
    { path: 'get-involved', component: GetInvolvedComponent },
    { path: 'news', component: NewsComponent },
    { path: 'gallery', component: GalleryComponent },
    { path: 'contact', component: ContactComponent },
    { path: 'donate', component: DonateComponent },
    { path: 'admin/login', component: LoginComponent },
    { path: 'admin/dashboard', component: DashboardComponent, canActivate: [authGuard] },
    { path: '**', redirectTo: '' }
];
