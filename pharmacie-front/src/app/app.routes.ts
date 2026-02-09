import { Routes } from '@angular/router';
import { SidebarComponent } from './sidebar/sidebar.component';
import { NavbarComponent } from './navbar/navbar.component';

export const routes: Routes = [
  { path: 'sidebar', component: SidebarComponent },
  { path: 'navbar', component: NavbarComponent }
];
