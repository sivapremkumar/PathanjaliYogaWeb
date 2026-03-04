import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../services/auth.service';
import { Router } from '@angular/router';
import { LucideAngularModule, Lock, User, Eye, EyeOff } from 'lucide-angular';

@Component({
    selector: 'app-login',
    standalone: true,
    imports: [CommonModule, FormsModule, LucideAngularModule],
    templateUrl: './login.component.html',
    styleUrls: ['./login.component.css']
})
export class LoginComponent {
    credentials = { username: '', password: '' };
    error = '';
    showPassword = false;

    readonly Lock = Lock;
    readonly User = User;
    readonly Eye = Eye;
    readonly EyeOff = EyeOff;

    constructor(private auth: AuthService, private router: Router) { }

    onSubmit() {
        this.auth.login(this.credentials).subscribe({
            next: () => {
                this.router.navigate(['/admin/dashboard']);
            },
            error: (err) => {
                this.error = 'Invalid username or password.';
            }
        });
    }
}
