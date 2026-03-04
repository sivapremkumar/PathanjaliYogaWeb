import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LucideAngularModule, Heart, GraduationCap, Briefcase, ArrowRight, CheckCircle } from 'lucide-angular';
import { RouterModule } from '@angular/router';

@Component({
    selector: 'app-get-involved',
    standalone: true,
    imports: [CommonModule, LucideAngularModule, RouterModule],
    templateUrl: './get-involved.component.html',
    styleUrls: ['./get-involved.component.css']
})
export class GetInvolvedComponent {
    readonly Heart = Heart;
    readonly GraduationCap = GraduationCap;
    readonly Briefcase = Briefcase;
    readonly ArrowRight = ArrowRight;
    readonly CheckCircle = CheckCircle;
}
