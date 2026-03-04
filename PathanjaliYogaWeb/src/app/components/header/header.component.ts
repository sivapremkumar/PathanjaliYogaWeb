import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { LucideAngularModule, Menu, X, Phone, Mail, Facebook, Instagram, Youtube } from 'lucide-angular';
import { AuthService } from '../../services/auth.service';

@Component({
    selector: 'app-header',
    standalone: true,
    imports: [CommonModule, RouterModule, LucideAngularModule],
    templateUrl: './header.component.html',
    styleUrls: ['./header.component.css']
})
export class HeaderComponent implements OnInit {
    isMenuOpen = false;
    isLoggedIn = false;

    readonly Menu = Menu;
    readonly X = X;
    readonly Phone = Phone;
    readonly Mail = Mail;
    readonly Facebook = Facebook;
    readonly Instagram = Instagram;
    readonly Youtube = Youtube;

    constructor(private auth: AuthService) { }

    ngOnInit() {
        this.isLoggedIn = this.auth.isLoggedIn();
    }

    toggleMenu() {
        this.isMenuOpen = !this.isMenuOpen;
    }
}
