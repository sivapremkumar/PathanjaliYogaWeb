import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Plus, Trash2, Edit } from 'lucide-angular';

@Component({
    selector: 'app-trustees',
    standalone: true,
    imports: [CommonModule, FormsModule, LucideAngularModule],
    templateUrl: './trustees.component.html'
})
export class TrusteesComponent implements OnInit {
    trustees: any[] = [];
    newTrustee = { name: '', role: '', description: '', imageUrl: '' };
    showForm = false;

    readonly Plus = Plus;
    readonly Trash2 = Trash2;
    readonly Edit = Edit;

    constructor(private api: ApiService) { }

    ngOnInit() {
        this.loadTrustees();
    }

    loadTrustees() {
        this.api.getTrustees().subscribe(data => this.trustees = data);
    }

    addTrustee() {
        this.api.createTrustee(this.newTrustee).subscribe(() => {
            this.loadTrustees();
            this.newTrustee = { name: '', role: '', description: '', imageUrl: '' };
            this.showForm = false;
        });
    }

    deleteTrustee(id: number) {
        if (confirm('Are you certain you want to remove this trustee?')) {
            this.api.deleteTrustee(id).subscribe(() => this.loadTrustees());
        }
    }
}
